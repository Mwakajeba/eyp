<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class AssetMovementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','company.scope','require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        
        // Check permission
        if (!$user->hasPermissionTo('view asset movements')) {
            return back()->withErrors(['error' => 'You do not have permission to view asset movements.']);
        }
        
        return view('assets.movements.index');
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $query = AssetMovement::forCompany($user->company_id)
            ->with([
                'asset',
                'fromBranch',
                'toBranch',
                'fromDepartment',
                'toDepartment',
                'fromUser',
                'toUser'
            ])
            ->orderByDesc('id');

        return DataTables::of($query)
            ->editColumn('movement_voucher', function($m) {
                return '<div class="fw-semibold text-primary">' . $m->movement_voucher . '</div>';
            })
            ->addColumn('asset_info', function($m) {
                $assetName = optional($m->asset)->name ?? 'N/A';
                $assetCode = optional($m->asset)->code ?? 'N/A';
                return '<div class="d-flex align-items-center">' .
                    '<i class="bx bx-cube me-2 text-primary"></i>' .
                    '<div>' .
                    '<div class="fw-semibold">' . $assetName . '</div>' .
                    '<small class="text-muted">' . $assetCode . '</small>' .
                    '</div>' .
                    '</div>';
            })
            ->addColumn('from_location', function($m) {
                $html = '<div class="small">';
                $html .= '<div class="mb-1"><i class="bx bx-building me-1 text-muted"></i><span class="text-muted">Branch:</span> ' . (optional($m->fromBranch)->name ?? 'N/A') . '</div>';
                $html .= '<div class="mb-1"><i class="bx bx-group me-1 text-muted"></i><span class="text-muted">Dept:</span> ' . (optional($m->fromDepartment)->name ?? 'N/A') . '</div>';
                $html .= '<div><i class="bx bx-user me-1 text-muted"></i><span class="text-muted">User:</span> ' . (optional($m->fromUser)->name ?? 'N/A') . '</div>';
                $html .= '</div>';
                return $html;
            })
            ->addColumn('to_location', function($m) {
                $html = '<div class="small">';
                $html .= '<div class="mb-1"><i class="bx bx-building me-1 text-success"></i><span class="text-muted">Branch:</span> ' . (optional($m->toBranch)->name ?? 'No change') . '</div>';
                $html .= '<div class="mb-1"><i class="bx bx-group me-1 text-success"></i><span class="text-muted">Dept:</span> ' . (optional($m->toDepartment)->name ?? 'No change') . '</div>';
                $html .= '<div><i class="bx bx-user me-1 text-success"></i><span class="text-muted">User:</span> ' . (optional($m->toUser)->name ?? 'No change') . '</div>';
                $html .= '</div>';
                return $html;
            })
            ->editColumn('status', function($m) {
                $statusClass = $m->status === 'completed' ? 'success' : ($m->status === 'approved' ? 'info' : ($m->status === 'rejected' ? 'danger' : 'secondary'));
                $statusText = ucfirst(str_replace('_', ' ', $m->status));
                return '<span class="badge bg-' . $statusClass . ' px-3 py-2">' . $statusText . '</span>';
            })
            ->editColumn('initiated_at', function($m) {
                if (!$m->initiated_at) return 'N/A';
                $html = '<div class="small">';
                $html .= '<div>' . $m->initiated_at->format('d M Y') . '</div>';
                $html .= '<small class="text-muted">' . $m->initiated_at->format('H:i') . '</small>';
                $html .= '</div>';
                return $html;
            })
            ->addColumn('actions', function($m) {
                return '<a href="' . route('assets.movements.show', $m->id) . '" class="btn btn-sm btn-outline-primary" title="View Details"><i class="bx bx-show"></i></a>';
            })
            ->rawColumns(['movement_voucher', 'asset_info', 'from_location', 'to_location', 'status', 'initiated_at', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $user = Auth::user();
        
        // Check permission
        if (!$user->hasPermissionTo('create asset movements')) {
            return back()->withErrors(['error' => 'You do not have permission to create asset movements.']);
        }
        
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        
        $assets = Asset::where('company_id', $user->company_id)
            ->when($branchId, function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get(['id','name','code','branch_id','department_id','custodian_user_id']);
        return view('assets.movements.create', compact('assets'));
    }

    public function store(Request $request)
    {
        
        info("=== Asset Movement Store Started ===");
        info("Request data: " . json_encode($request->all()));
        
        try {
            $request->validate([
                'asset_id' => 'required|exists:assets,id',
                'to_branch_id' => 'nullable|exists:branches,id',
                'to_department_id' => 'nullable|exists:hr_departments,id',
                'to_user_id' => 'nullable|exists:users,id',
                'reason' => 'nullable|string|max:255',
                'gl_post' => 'nullable|boolean',
            ]);
            info("Validation passed");
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation failed: " . json_encode($e->errors()));
            file_put_contents(storage_path('logs/laravel.log'), 
                "[" . date('Y-m-d H:i:s') . "] VALIDATION ERROR: " . json_encode($e->errors()) . "\n", 
                FILE_APPEND
            );
            throw $e;
        }

        $user = Auth::user();
        info("User: {$user->id} ({$user->name}), Company: {$user->company_id}");
        
        $asset = Asset::findOrFail($request->asset_id);
        info("Asset found: {$asset->id} ({$asset->name}), Status: {$asset->status}");

        // Status check
        if (in_array($asset->status, ['disposed','inactive','retired'])) {
            Log::warning("Asset {$asset->id} cannot be moved - status: {$asset->status}");
            return back()->withErrors(['asset_id' => 'This asset cannot be moved due to its current status.']);
        }

        DB::beginTransaction();
        info("Transaction started");
        try {
            $voucher = 'AMV-' . now()->format('Ymd') . '-' . str_pad((string) ((int) (AssetMovement::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT);
            info("Voucher generated: {$voucher}");

            $glPost = (bool) $request->boolean('gl_post');
            info("GL Post setting: " . ($glPost ? 'Yes' : 'No'));

            $movement = AssetMovement::create([
                'company_id' => $user->company_id,
                'asset_id' => $asset->id,
                'from_branch_id' => $asset->branch_id,
                'from_department_id' => $asset->department_id,
                'from_user_id' => $asset->custodian_user_id,
                'to_branch_id' => $request->to_branch_id,
                'to_department_id' => $request->to_department_id,
                'to_user_id' => $request->to_user_id,
                'movement_voucher' => $voucher,
                'reason' => $request->reason,
                'status' => 'pending_review',
                'initiated_at' => now(),
                'initiated_by' => $user->id,
                'gl_post' => $glPost,
            ]);

            info("Movement created: ID {$movement->id}, Voucher: {$movement->movement_voucher}");

            DB::commit();
            info("Transaction committed");
            info("=== Asset Movement Store Completed Successfully ===");
            return redirect()->route('assets.movements.show', $movement->id)->with('success','Movement initiated');
        } catch (\Exception $e) {
            Log::error("Error creating movement: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            file_put_contents(storage_path('logs/laravel.log'), 
                "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n", 
                FILE_APPEND
            );
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        
        // Check permission
        if (!$user->hasPermissionTo('view asset movements')) {
            return back()->withErrors(['error' => 'You do not have permission to view asset movements.']);
        }
        
        $movement = AssetMovement::forCompany($user->company_id)
            ->with([
                'asset',
                'fromBranch',
                'toBranch',
                'fromDepartment',
                'toDepartment',
                'fromUser',
                'toUser',
                'journal.items.chartAccount'
            ])
            ->findOrFail($id);
        return view('assets.movements.show', compact('movement'));
    }

    public function approve($id)
    {
        $user = Auth::user();
        
        // Check permission
        if (!$user->hasPermissionTo('approve asset movements')) {
            return back()->withErrors(['error' => 'You do not have permission to approve asset movements.']);
        }
        
        info("Approve method called with ID: {$id}");
        $movement = AssetMovement::forCompany($user->company_id)->findOrFail($id);
        if (!in_array($movement->status, ['pending_review'])) {
            return back()->withErrors(['error' => 'Movement is not pending review.']);
        }
        $movement->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);
        return back()->with('success','Movement approved');
    }

    public function complete($id, Request $request = null)
    {
        // Use request() helper if $request is null (for compatibility)
        $req = $request ?? request();
        
        // Force write to log file immediately - this MUST execute if method is called
        $logMessage = "[" . date('Y-m-d H:i:s') . "] TEST: complete() method called with ID: " . var_export($id, true) . ", Method: " . $req->method() . ", URL: " . $req->fullUrl() . "\n";
        file_put_contents(storage_path('logs/laravel.log'), $logMessage, FILE_APPEND);
        
        // Log at the very start - use error_log to ensure it's written
        error_log("=== Asset Movement Complete Started ===");
        error_log("Raw Movement ID from route: " . var_export($id, true));
        error_log("Request method: " . $req->method());
        error_log("Request URL: " . $req->fullUrl());
        
        // Also use Log facade
        try {
            info("=== Asset Movement Complete Started ===");
            info("Raw Movement ID from route: " . var_export($id, true));
            info("Request method: " . $req->method());
            info("Request URL: " . $req->fullUrl());
        } catch (\Exception $logError) {
            file_put_contents(storage_path('logs/laravel.log'), 
                "[" . date('Y-m-d H:i:s') . "] ERROR: Log facade failed: " . $logError->getMessage() . "\n", 
                FILE_APPEND
            );
        }
        
        try {
            $user = Auth::user();
            if (!$user) {
                Log::error("No authenticated user found");
                return back()->withErrors(['error' => 'You must be logged in to complete a movement.']);
            }
            
            // Check permission
            if (!$user->hasPermissionTo('complete asset movements')) {
                return back()->withErrors(['error' => 'You do not have permission to complete asset movements.']);
            }
            
            info("User: {$user->id} ({$user->name}), Company: {$user->company_id}, Branch: " . (session('branch_id') ?? 'NULL'));
            
            // Try to find the movement
            info("Attempting to find movement with ID: {$id}");
            $movement = AssetMovement::forCompany($user->company_id)
                ->with(['asset.category', 'fromBranch', 'toBranch', 'fromDepartment', 'toDepartment'])
                ->find($id);
            
            if (!$movement) {
                Log::error("Movement not found. ID: {$id}, Company: {$user->company_id}");
                return back()->withErrors(['error' => 'Movement not found.']);
            }
            
            info("Movement found: {$movement->movement_voucher}, Status: {$movement->status}, GL Post: " . ($movement->gl_post ? 'Yes' : 'No'));
            
            if ($movement->status !== 'approved') {
                Log::warning("Movement {$movement->movement_voucher} is not approved. Current status: {$movement->status}");
                return back()->withErrors(['error' => 'Movement must be approved before completion.']);
            }

            DB::beginTransaction();
            info("Transaction started");
            
            try {
                // Update asset to new location/custodian
                $asset = $movement->asset ?? Asset::with('category')->findOrFail($movement->asset_id);
                info("Asset loaded: {$asset->id} ({$asset->name}), Current branch: {$asset->branch_id}, Current dept: {$asset->department_id}");
                
                $asset->update([
                    'branch_id' => $movement->to_branch_id ?? $asset->branch_id,
                    'department_id' => $movement->to_department_id ?? $asset->department_id,
                    'custodian_user_id' => $movement->to_user_id ?? $asset->custodian_user_id,
                ]);
                info("Asset updated - New branch: " . ($movement->to_branch_id ?? $asset->branch_id) . ", New dept: " . ($movement->to_department_id ?? $asset->department_id));

                // GL posting for cost center transfers
                if ($movement->gl_post) {
                    info("GL posting is enabled for movement {$movement->movement_voucher}");
                    
                    // Get current Net Book Value
                    info("Calculating current NBV for asset {$asset->id}");
                    $currentNBV = \App\Models\Assets\AssetDepreciation::getCurrentBookValue(
                        $asset->id,
                        now(),
                        $user->company_id
                    );
                    info("Current NBV calculated: {$currentNBV}");
                    
                    if ($currentNBV > 0) {
                        // Get asset account from category or system settings
                        $category = $asset->category;
                        info("Category: " . ($category ? "{$category->id} ({$category->name})" : 'NULL'));
                        info("Category asset_account_id: " . ($category?->asset_account_id ?? 'NULL'));
                        
                        $assetAccountId = (int) ($category?->asset_account_id
                            ?: (\App\Models\SystemSetting::where('key', 'asset_default_asset_account')->value('value') ?? 0));
                        info("Asset Account ID resolved: {$assetAccountId}");
                        
                        if ($assetAccountId) {
                            // Use current session branch_id for all journal and GL entries (branch-wise context)
                            $journalBranchId = session('branch_id') ?? $user->branch_id ?? null;
                            info("Journal Branch ID: {$journalBranchId} (session: " . (session('branch_id') ?? 'NULL') . ", user: " . ($user->branch_id ?? 'NULL') . ")");
                            
                            if (!$journalBranchId) {
                                Log::warning("Asset movement {$movement->movement_voucher}: No branch_id available for GL posting");
                                throw new \Exception('Branch ID is required for GL posting. Please select a branch.');
                            }
                            
                            // Create journal entry
                            info("Creating journal entry...");
                            info("Journal data: date=" . now()->format('Y-m-d H:i:s') . ", reference={$movement->movement_voucher}, branch_id={$journalBranchId}, user_id={$user->id}");
                            
                            try {
                                $journal = \App\Models\Journal::create([
                                    'date' => now(),
                                    'reference' => $movement->movement_voucher,
                                    'reference_type' => 'asset_movement',
                                    'description' => "Asset Movement: {$asset->name} ({$asset->code}) - Reclassification from " 
                                        . ($movement->fromDepartment ? $movement->fromDepartment->name : 'N/A')
                                        . ($movement->fromBranch ? " ({$movement->fromBranch->name})" : '')
                                        . " to " 
                                        . ($movement->toDepartment ? $movement->toDepartment->name : 'N/A')
                                        . ($movement->toBranch ? " ({$movement->toBranch->name})" : ''),
                                    'branch_id' => $journalBranchId,
                                    'user_id' => $user->id,
                                ]);
                                info("Journal created successfully: ID {$journal->id}, Reference: {$journal->reference}");
                            } catch (\Exception $journalError) {
                                Log::error("Failed to create journal: " . $journalError->getMessage());
                                Log::error("Journal error trace: " . $journalError->getTraceAsString());
                                throw $journalError;
                            }
                            
                            // Create journal items: Debit new location, Credit old location
                            info("Creating journal items...");
                            // Debit: New department's asset account
                            try {
                                $debitItem = \App\Models\JournalItem::create([
                                    'journal_id' => $journal->id,
                                    'chart_account_id' => $assetAccountId,
                                    'amount' => $currentNBV,
                                    'nature' => 'debit',
                                    'description' => "Asset reclassification - To: " 
                                        . ($movement->toDepartment ? $movement->toDepartment->name : 'N/A')
                                        . ($movement->toBranch ? " ({$movement->toBranch->name})" : ''),
                                ]);
                                info("Debit item created: ID {$debitItem->id}, Amount: {$currentNBV}, Account: {$assetAccountId}");
                            } catch (\Exception $debitError) {
                                Log::error("Failed to create debit item: " . $debitError->getMessage());
                                throw $debitError;
                            }
                            
                            // Credit: Old department's asset account
                            try {
                                $creditItem = \App\Models\JournalItem::create([
                                    'journal_id' => $journal->id,
                                    'chart_account_id' => $assetAccountId,
                                    'amount' => $currentNBV,
                                    'nature' => 'credit',
                                    'description' => "Asset reclassification - From: " 
                                        . ($movement->fromDepartment ? $movement->fromDepartment->name : 'N/A')
                                        . ($movement->fromBranch ? " ({$movement->fromBranch->name})" : ''),
                                ]);
                                info("Credit item created: ID {$creditItem->id}, Amount: {$currentNBV}, Account: {$assetAccountId}");
                            } catch (\Exception $creditError) {
                                Log::error("Failed to create credit item: " . $creditError->getMessage());
                                throw $creditError;
                            }
                            
                            // Create GL transactions for each journal item
                            info("Creating GL transactions...");
                            $journalItems = [$debitItem, $creditItem];
                            foreach ($journalItems as $index => $item) {
                                info("Creating GL transaction " . ($index + 1) . " for journal item {$item->id}");
                                try {
                                    $glTransaction = \App\Models\GlTransaction::create([
                                        'chart_account_id' => $item->chart_account_id,
                                        'amount' => $item->amount,
                                        'nature' => $item->nature,
                                        'transaction_id' => $journal->id,
                                        'transaction_type' => 'journal',
                                        'date' => $journal->date,
                                        'description' => $item->description,
                                        'branch_id' => $journalBranchId,
                                        'user_id' => $user->id,
                                    ]);
                                    info("GL Transaction created: ID {$glTransaction->id}");
                                } catch (\Exception $glError) {
                                    Log::error("Failed to create GL transaction " . ($index + 1) . ": " . $glError->getMessage());
                                    Log::error("GL transaction data: account_id={$item->chart_account_id}, amount={$item->amount}, nature={$item->nature}, branch_id={$journalBranchId}");
                                    throw $glError;
                                }
                            }
                            
                            info("Updating movement with GL posting info...");
                            $movement->gl_posted = true;
                            $movement->gl_posted_at = now();
                            $movement->journal_id = $journal->id;
                            $movement->notes = trim(($movement->notes ?? '') . "\nGL Posted: Journal #{$journal->id} - NBV: " . number_format($currentNBV, 2));
                            info("Movement GL info updated");
                        } else {
                            // No asset account configured
                            Log::warning("Asset movement {$movement->movement_voucher}: No asset account configured for GL posting");
                        }
                    } else {
                        // NBV is zero or negative
                        info("Asset movement {$movement->movement_voucher}: NBV is {$currentNBV}, skipping GL posting");
                    }
                } else {
                    info("GL posting is disabled for movement {$movement->movement_voucher}");
                }

                info("Updating movement status to completed...");
                $movement->status = 'completed';
                $movement->completed_at = now();
                $movement->completed_by = $user->id;
                
                try {
                    $saved = $movement->save();
                    info("Movement save result: " . ($saved ? 'true' : 'false'));
                    info("Movement status updated and saved. Movement ID: {$movement->id}, Status: {$movement->status}");
                } catch (\Exception $saveError) {
                    Log::error("Failed to save movement: " . $saveError->getMessage());
                    Log::error("Save error trace: " . $saveError->getTraceAsString());
                    throw $saveError;
                }

                DB::commit();
                info("Transaction committed successfully");
                info("=== Asset Movement Complete Finished Successfully ===");
                return back()->with('success','Movement completed');
            } catch (\Exception $e) {
                Log::error("Error during movement completion: " . $e->getMessage());
                Log::error("Stack trace: " . $e->getTraceAsString());
                DB::rollBack();
                info("Transaction rolled back");
                info("=== Asset Movement Complete Failed ===");
                return back()->withErrors(['error' => $e->getMessage()]);
            }
        } catch (\Exception $e) {
            Log::error("Error loading movement: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            info("=== Asset Movement Complete Failed ===");
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reject($id, Request $request)
    {
        $user = Auth::user();
        
        // Check permission
        if (!$user->hasPermissionTo('reject asset movements')) {
            return back()->withErrors(['error' => 'You do not have permission to reject asset movements.']);
        }
        
        $request->validate(['reason' => 'required|string|max:255']);
        $movement = AssetMovement::forCompany($user->company_id)->findOrFail($id);
        if (!in_array($movement->status, ['pending_review','approved'])) {
            return back()->withErrors(['error' => 'Movement is not pending or approved.']);
        }
        $movement->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'notes' => trim((string) $movement->notes . "\nRejected: " . $request->reason)
        ]);
        return back()->with('success','Movement rejected');
    }

    // Lookup endpoints for dependent selects
    public function departmentsByBranch(Request $request)
    {
        $branchId = (int) $request->get('branch_id');
        if (!$branchId) { return response()->json([]); }
        $departments = \App\Models\Hr\Department::where('branch_id', $branchId)
            ->orderBy('name')->get(['id','name']);
        return response()->json($departments);
    }

    public function usersByBranch(Request $request)
    {
        $branchId = (int) $request->get('branch_id');
        if (!$branchId) { return response()->json([]); }
        // Users assigned to branch via pivot branch_user
        $userIds = \App\Models\BranchUser::where('branch_id', $branchId)->pluck('user_id');
        $users = \App\Models\User::whereIn('id', $userIds)->orderBy('name')->get(['id','name']);
        return response()->json($users);
    }

    public function assetDetails(Request $request)
    {
        try {
            $assetId = (int) $request->get('asset_id');
            if (!$assetId) { 
                return response()->json([
                    'branch' => null,
                    'department' => null,
                    'custodian' => null,
                ]); 
            }
            
            $user = Auth::user();
            $branchId = session('branch_id') ?? $user->branch_id ?? null;
            
            // Don't filter by branch_id here - we want to find the asset regardless
            // and use session branch as fallback if asset doesn't have branch_id set
            $asset = Asset::where('company_id', $user->company_id)
                ->with(['branch', 'department', 'custodian'])
                ->find($assetId);
            
            if (!$asset) { 
                return response()->json([
                    'branch' => null,
                    'department' => null,
                    'custodian' => null,
                ]); 
            }
            
            // Get branch name - try relationship first, then direct lookup, then session branch
            $branchName = null;
            if ($asset->branch) {
                $branchName = $asset->branch->name;
            } elseif ($asset->branch_id) {
                $branch = \App\Models\Branch::where('company_id', $user->company_id)
                    ->find($asset->branch_id);
                $branchName = $branch ? $branch->name : null;
            } elseif ($branchId) {
                // Fallback to session branch if asset doesn't have branch_id set
                $branch = \App\Models\Branch::where('company_id', $user->company_id)
                    ->find($branchId);
                $branchName = $branch ? $branch->name : null;
            }
            
            // Get department name - try relationship first, then direct lookup
            $departmentName = null;
            if ($asset->department) {
                $departmentName = $asset->department->name;
            } elseif ($asset->department_id) {
                $department = \App\Models\Hr\Department::find($asset->department_id);
                $departmentName = $department ? $department->name : null;
            }
            
            // Get custodian name - try relationship first, then direct lookup
            $custodianName = null;
            if ($asset->custodian) {
                $custodianName = $asset->custodian->name;
            } elseif ($asset->custodian_user_id) {
                $custodian = \App\Models\User::where('company_id', $user->company_id)
                    ->find($asset->custodian_user_id);
                $custodianName = $custodian ? $custodian->name : null;
            }
            
            return response()->json([
                'branch' => $branchName,
                'department' => $departmentName,
                'custodian' => $custodianName,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching asset details: ' . $e->getMessage());
            return response()->json([
                'branch' => null,
                'department' => null,
                'custodian' => null,
            ], 500);
        }
    }
}


