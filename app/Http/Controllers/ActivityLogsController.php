<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get users for filter dropdown
        $users = User::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        // Get unique models for filter
        // Check if company_id column exists, if not, get all models
        $modelsQuery = ActivityLog::query();
        if (\Schema::hasColumn('activity_logs', 'company_id')) {
            $modelsQuery->where('company_id', $user->company_id);
        }
        
        $models = $modelsQuery->distinct()
            ->pluck('model')
            ->filter()
            ->sort()
            ->values();

        return view('logs.index', compact('users', 'models'));
    }

    /**
     * DataTables AJAX endpoint for activity logs
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        
        // If summary only requested
        if ($request->has('summary_only')) {
            // Helper function to build base query with filters
            $buildQuery = function() use ($request, $user) {
                $query = ActivityLog::query();
                
                // Only filter by company_id if column exists
                if (Schema::hasColumn('activity_logs', 'company_id')) {
                    $query->where('company_id', $user->company_id);
                }
                
                if ($request->filled('user_id')) {
                    $query->where('user_id', $request->user_id);
                }
                if ($request->filled('model')) {
                    $query->where('model', $request->model);
                }
                if ($request->filled('action')) {
                    $query->where('action', $request->action);
                }
                if ($request->filled('date_from')) {
                    $query->whereDate('activity_time', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $query->whereDate('activity_time', '<=', $request->date_to);
                }
                
                return $query;
            };
            
            return response()->json([
                'summary' => [
                    'total' => $buildQuery()->count(),
                    'fx_revaluations' => $buildQuery()->where('model', 'GlRevaluationHistory')->count(),
                    'fx_rates' => $buildQuery()->where('model', 'FxRate')->count(),
                    'today' => $buildQuery()->whereDate('activity_time', today())->count(),
                ]
            ]);
        }
        
        $query = ActivityLog::with(['user']);
        
        // Only filter by company_id if column exists
        if (Schema::hasColumn('activity_logs', 'company_id')) {
            $query->where('company_id', $user->company_id);
        }
        
        $query->select('activity_logs.*');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request, $user) {
                // Apply filters from request
                if ($request->filled('user_id')) {
                    $query->where('user_id', $request->user_id);
                }

                if ($request->filled('model')) {
                    $query->where('model', $request->model);
                }

                if ($request->filled('action')) {
                    $query->where('action', $request->action);
                }

                if ($request->filled('date_from')) {
                    $query->whereDate('activity_time', '>=', $request->date_from);
                }

                if ($request->filled('date_to')) {
                    $query->whereDate('activity_time', '<=', $request->date_to);
                }

                // Global search
                if ($request->filled('search.value')) {
                    $searchValue = $request->input('search.value');
                    $query->where(function($q) use ($searchValue) {
                        $q->where('description', 'like', "%{$searchValue}%")
                          ->orWhere('model', 'like', "%{$searchValue}%")
                          ->orWhere('action', 'like', "%{$searchValue}%")
                          ->orWhere(function($userQ) use ($searchValue) {
                              $userQ->whereHas('user', function($userQuery) use ($searchValue) {
                                  $userQuery->where('name', 'like', "%{$searchValue}%");
                              });
                          });
                    });
                }
            })
            ->addColumn('formatted_date', function ($log) {
                return $log->activity_time ? $log->activity_time->format('M d, Y H:i:s') : 'N/A';
            })
            ->addColumn('user_name', function ($log) {
                return $log->user ? $log->user->name : 'System';
            })
            ->addColumn('model_badge', function ($log) {
                return $log->model_badge;
            })
            ->addColumn('action_badge', function ($log) {
                return $log->action_badge;
            })
            ->addColumn('detailed_description', function ($log) {
                $description = $log->description ?? 'N/A';
                
                // Add FX-specific details for revaluation logs
                if ($log->model === 'GlRevaluationHistory' && $log->model_id) {
                    try {
                        $reval = \App\Models\GlRevaluationHistory::find($log->model_id);
                        if ($reval) {
                            $description .= '<br><small class="text-muted">';
                            $description .= '<strong>Item:</strong> ' . $reval->item_type . ' - ' . $reval->item_ref . ' | ';
                            $description .= '<strong>Gain/Loss:</strong> ' . $reval->formatted_gain_loss . ' | ';
                            $description .= '<strong>Date:</strong> ' . $reval->revaluation_date->format('Y-m-d');
                            $description .= '</small>';
                        }
                    } catch (\Exception $e) {
                        // Silently fail if revaluation not found
                    }
                }
                
                // Add FX rate details
                if ($log->model === 'FxRate' && $log->model_id) {
                    try {
                        $rate = \App\Models\FxRate::find($log->model_id);
                        if ($rate) {
                            $description .= '<br><small class="text-muted">';
                            $description .= '<strong>Pair:</strong> ' . $rate->from_currency . '/' . $rate->to_currency . ' | ';
                            $description .= '<strong>Rate:</strong> ' . number_format($rate->spot_rate, 6) . ' | ';
                            $description .= '<strong>Date:</strong> ' . $rate->rate_date->format('Y-m-d');
                            $description .= '</small>';
                        }
                    } catch (\Exception $e) {
                        // Silently fail if rate not found
                    }
                }
                
                return $description;
            })
            ->addColumn('related_link', function ($log) {
                $links = '<div class="btn-group">';
                
                // Always show view log details link
                $links .= '<a href="'.route('settings.logs.show', $log->id).'" class="btn btn-sm btn-info" title="View Log Details"><i class="bx bx-show"></i></a>';
                
                // Show related record link if applicable
                if ($log->model === 'GlRevaluationHistory' && $log->model_id) {
                    $links .= '<a href="'.route('accounting.fx-revaluation.show', $log->model_id).'" class="btn btn-sm btn-primary" title="View Revaluation"><i class="bx bx-refresh"></i></a>';
                } elseif ($log->model === 'FxRate' && $log->model_id) {
                    $links .= '<a href="'.route('accounting.fx-rates.edit', $log->model_id).'" class="btn btn-sm btn-primary" title="View Rate"><i class="bx bx-dollar"></i></a>';
                }
                
                $links .= '</div>';
                return $links;
            })
            ->rawColumns(['model_badge', 'action_badge', 'detailed_description', 'related_link'])
            ->orderColumn('activity_time', 'activity_time $1')
            ->make(true);
    }

    public function show($id)
    {
        $user = Auth::user();
        
        $query = ActivityLog::with(['user']);
        
        // Only filter by company_id if column exists
        if (Schema::hasColumn('activity_logs', 'company_id')) {
            $query->where('company_id', $user->company_id);
        }
        
        $log = $query->findOrFail($id);
        
        return view('activity_logs.show', compact('log'));
    }
}
