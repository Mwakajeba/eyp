<?php

namespace App\Http\Controllers;

use App\Models\Retirement;
use App\Models\RetirementItem;
use App\Models\ImprestRequest;
use App\Models\ChartAccount;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use DataTables;

class RetirementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        
        $stats = [
            'total_retirements' => Retirement::forCompany($user->company_id)->count(),
            'pending' => Retirement::forCompany($user->company_id)->byStatus('pending')->count(),
            'checked' => Retirement::forCompany($user->company_id)->byStatus('checked')->count(),
            'approved' => Retirement::forCompany($user->company_id)->byStatus('approved')->count(),
            'rejected' => Retirement::forCompany($user->company_id)->byStatus('rejected')->count(),
        ];

        // Get branches for filtering
        $branches = Branch::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        // Get employees for filtering
        $employees = User::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('imprest.retirement.index', compact('stats', 'branches', 'employees'));
    }

    /**
     * Get retirement data for DataTable
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        
        $query = Retirement::with([
            'imprestRequest.employee',
            'imprestRequest.department',
            'imprestRequest.branch',
            'branch',
            'submitter'
        ])->forCompany($user->company_id);

        // Apply filters from request
        if ($request->has('status') && !empty($request->input('status'))) {
            $query->byStatus($request->input('status'));
        }

        if ($request->has('branch_id') && !empty($request->input('branch_id'))) {
            $query->whereHas('imprestRequest', function ($q) use ($request) {
                $q->where('branch_id', $request->input('branch_id'));
            });
        }

        if ($request->has('employee_id') && !empty($request->input('employee_id'))) {
            $query->whereHas('imprestRequest', function ($q) use ($request) {
                $q->where('employee_id', $request->input('employee_id'));
            });
        }

        if ($request->has('start_date') && !empty($request->input('start_date'))) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date') && !empty($request->input('end_date'))) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        return DataTables::of($query)
            ->addColumn('imprest_request_number', function ($retirement) {
                return $retirement->imprestRequest->request_number ?? 'N/A';
            })
            ->addColumn('imprest_request_id', function ($retirement) {
                return $retirement->imprestRequest->id ?? '';
            })
            ->addColumn('employee_name', function ($retirement) {
                return $retirement->imprestRequest->employee->name ?? 'N/A';
            })
            ->addColumn('branch_name', function ($retirement) {
                return $retirement->imprestRequest->branch->name ?? 'N/A';
            })
            ->addColumn('disbursed_amount', function ($retirement) {
                return $retirement->imprestRequest->disbursed_amount ?? 0;
            })
            ->addColumn('total_amount_used', function ($retirement) {
                $items = $retirement->retirementItems()->sum('actual_amount');
                return $items ?? 0;
            })
            ->addColumn('remaining_balance', function ($retirement) {
                $disbursed = $retirement->imprestRequest->disbursed_amount ?? 0;
                $used = $retirement->retirementItems()->sum('actual_amount') ?? 0;
                return $disbursed - $used;
            })
            ->addColumn('submitted_at', function ($retirement) {
                return $retirement->created_at?->toIso8601String() ?? '';
            })
            ->addColumn('can_edit', function ($retirement) {
                return $retirement->status === 'pending' && $retirement->submitted_by === auth()->id();
            })
            ->addColumn('can_check', function ($retirement) {
                // Check if user has permission to check retirements
                return auth()->user()->can('check', $retirement);
            })
            ->addColumn('actions', function ($retirement) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('imprest.retirement.show', $retirement->id) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                
                if ($retirement->status === 'pending' && $retirement->submitted_by === auth()->id()) {
                    $actions .= '<a href="' . route('imprest.retirement.edit', $retirement->id) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bx bx-edit"></i></a>';
                    $actions .= '<button onclick="deleteRetirement(' . $retirement->id . ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bx bx-trash"></i></button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource (retirement submission).
     */
    public function create($imprestRequestId)
    {
        $imprestRequest = ImprestRequest::with(['imprestItems.chartAccount', 'employee', 'department'])
            ->findOrFail($imprestRequestId);
        
        // Check if retirement can be created
        if (!$imprestRequest->canBeRetired()) {
            return redirect()->route('imprest.requests.show', $imprestRequestId)
                ->withErrors(['error' => 'This imprest request cannot be retired at this time.']);
        }

        // Get all chart accounts for the dropdown
        $chartAccounts = ChartAccount::whereHas('accountClassGroup', function($q) {
            $q->where('company_id', Auth::user()->company_id);
        })
        ->orderBy('account_name')
        ->get();

        // Get imprest items to pre-populate the retirement items
        $imprestItems = $imprestRequest->imprestItems;

        return view('imprest.retirement.create', compact('imprestRequest', 'chartAccounts', 'imprestItems'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $imprestRequestId)
    {
        $imprestRequest = ImprestRequest::findOrFail($imprestRequestId);
        
        if (!$imprestRequest->canBeRetired()) {
            return back()->withErrors(['error' => 'This imprest request cannot be retired.']);
        }

        $request->validate([
            'retirement_notes' => 'nullable|string|max:2000',
            'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'retirement_items' => 'required|array|min:1',
            'retirement_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'retirement_items.*.requested_amount' => 'required|numeric|min:0',
            'retirement_items.*.actual_amount' => 'required|numeric|min:0',
            'retirement_items.*.description' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            // Calculate total amount used
            $totalAmountUsed = collect($request->retirement_items)->sum('actual_amount');

            // Handle file upload
            $documentPath = null;
            if ($request->hasFile('supporting_document')) {
                $file = $request->file('supporting_document');
                $fileName = time() . '_retirement_' . $imprestRequest->request_number . '.' . $file->getClientOriginalExtension();
                $documentPath = $file->storeAs('retirement-documents', $fileName, 'public');
            }

            // Create retirement record
            $retirement = Retirement::create([
                'retirement_number' => Retirement::generateRetirementNumber(),
                'imprest_request_id' => $imprestRequest->id,
                'company_id' => $user->company_id,
                'branch_id' => $imprestRequest->branch_id,
                'total_amount_used' => $totalAmountUsed,
                'retirement_notes' => $request->retirement_notes,
                'supporting_document' => $documentPath,
                'status' => 'pending',
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ]);

            // Create retirement items
            foreach ($request->retirement_items as $item) {
                RetirementItem::create([
                    'retirement_id' => $retirement->id,
                    'chart_account_id' => $item['chart_account_id'],
                    'company_id' => $user->company_id,
                    'branch_id' => $imprestRequest->branch_id,
                    'requested_amount' => $item['requested_amount'],
                    'actual_amount' => $item['actual_amount'],
                    'description' => $item['description'],
                    'notes' => $item['notes'] ?? null,
                    'created_by' => $user->id,
                ]);
            }

            // Update imprest request status to liquidated (retirement replaces liquidation)
            $imprestRequest->update(['status' => 'liquidated']);

            // Check if multi-level approval is required
            if ($retirement->requiresApproval()) {
                $retirement->createApprovalRequests();
                $approvalMessage = ' Multi-level approval is required before processing.';
            } else {
                $approvalMessage = '';
            }

            DB::commit();

            \Log::info('Retirement created successfully', ['retirementId' => $retirement->id]);

            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Retirement submitted successfully and is now pending approval.' . $approvalMessage,
                    'redirect' => route('imprest.retirement.show', $retirement->id)
                ]);
            }

            return redirect()->route('imprest.retirement.show', $retirement->id)
                ->with('success', 'Retirement submitted successfully and is now pending approval.' . $approvalMessage);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            \Log::error('Retirement submission failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to submit retirement: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()
                ->withErrors(['error' => 'Failed to submit retirement: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $retirement = Retirement::with([
            'imprestRequest.employee', 'imprestRequest.department',
            'retirementItems.chartAccount',
            'submitter', 'checker', 'approver', 'rejecter'
        ])->findOrFail($id);

        // Check permissions
        $user = Auth::user();
        $canUserCheck = $retirement->canUserCheck($user);
        $canUserApprove = $retirement->canUserApprove($user);

        // Get credit accounts for journal creation (Cash, Bank, Payables)
        $companyId = $user->company_id;
        $creditAccounts = \App\Models\ChartAccount::whereExists(function($query) use ($companyId) {
            $query->select(\DB::raw(1))
                  ->from('account_class_groups')
                  ->whereColumn('chart_accounts.account_class_group_id', 'account_class_groups.id')
                  ->where('account_class_groups.company_id', $companyId);
        })->where(function($query) {
            $query->where('account_name', 'LIKE', '%Cash%')
                  ->orWhere('account_name', 'LIKE', '%Bank%')
                  ->orWhere('account_name', 'LIKE', '%Payable%')
                  ->orWhere('account_code', 'LIKE', '1%') // Assets
                  ->orWhere('account_code', 'LIKE', '2%'); // Liabilities
        })->orderBy('account_code')->get();

        // Handle AJAX requests
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'retirement_number' => $retirement->retirement_number,
                'employee' => $retirement->employee,
                'department' => $retirement->department,
                'purpose' => $retirement->imprestRequest->purpose,
                'total_retirement_amount' => $retirement->total_amount_used,
                'status' => $retirement->status,
                'description' => $retirement->retirement_notes,
                'created_at' => $retirement->created_at->toISOString(),
            ]);
        }

        return view('imprest.retirement.show', compact('retirement', 'canUserCheck', 'canUserApprove', 'creditAccounts'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $retirement = Retirement::with(['retirementItems.chartAccount'])->findOrFail($id);
        
        // Only allow editing if pending and user is the submitter
        $user = Auth::user();
        if ($retirement->status !== 'pending' || $retirement->submitted_by !== $user->id) {
            return redirect()->route('imprest.retirement.show', $id)
                ->withErrors(['error' => 'Cannot edit this retirement.']);
        }

        $chartAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })
        ->orderBy('account_name')
        ->get();

        return view('imprest.retirement.edit', compact('retirement', 'chartAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $retirement = Retirement::findOrFail($id);
        
        // Check permissions
        $user = Auth::user();
        if ($retirement->status !== 'pending' || $retirement->submitted_by !== $user->id) {
            return back()->withErrors(['error' => 'Cannot edit this retirement.']);
        }

        // Same validation as store
        $request->validate([
            'retirement_notes' => 'nullable|string|max:2000',
            'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'retirement_items' => 'required|array|min:1',
            'retirement_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'retirement_items.*.requested_amount' => 'required|numeric|min:0',
            'retirement_items.*.actual_amount' => 'required|numeric|min:0',
            'retirement_items.*.description' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        
        try {
            $branchId = session('branch_id') ?? ($user->branch_id ?? null);
            $totalAmountUsed = collect($request->retirement_items)->sum('actual_amount');

            // Handle file upload
            $documentPath = $retirement->supporting_document;
            if ($request->hasFile('supporting_document')) {
                // Delete old file if exists
                if ($documentPath) {
                    Storage::disk('public')->delete($documentPath);
                }
                
                $file = $request->file('supporting_document');
                $fileName = time() . '_retirement_' . $retirement->retirement_number . '.' . $file->getClientOriginalExtension();
                $documentPath = $file->storeAs('retirement-documents', $fileName, 'public');
            }

            // Update retirement
            $retirement->update([
                'total_amount_used' => $totalAmountUsed,
                'retirement_notes' => $request->retirement_notes,
                'supporting_document' => $documentPath,
            ]);

            // Delete old retirement items and create new ones
            $retirement->retirementItems()->delete();
            
            foreach ($request->retirement_items as $item) {
                RetirementItem::create([
                    'retirement_id' => $retirement->id,
                    'chart_account_id' => $item['chart_account_id'],
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'requested_amount' => $item['requested_amount'],
                    'actual_amount' => $item['actual_amount'],
                    'description' => $item['description'],
                    'notes' => $item['notes'] ?? null,
                    'created_by' => $user->id,
                ]);
            }

            DB::commit();

            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Retirement updated successfully.',
                    'redirect' => route('imprest.retirement.show', $retirement->id)
                ]);
            }

            return redirect()->route('imprest.retirement.show', $retirement->id)
                ->with('success', 'Retirement updated successfully.');
            
        } catch (\Exception $e) {
            DB::rollback();
            
            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update retirement: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()
                ->withErrors(['error' => 'Failed to update retirement: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $retirement = Retirement::findOrFail($id);
        
        // Only allow deletion if pending and user is the submitter
        $user = Auth::user();
        if ($retirement->status !== 'pending' || $retirement->submitted_by !== $user->id) {
            return back()->withErrors(['error' => 'Cannot delete this retirement.']);
        }

        try {
            // Delete supporting document if exists
            if ($retirement->supporting_document) {
                Storage::disk('public')->delete($retirement->supporting_document);
            }
            
            // Set imprest request back to disbursed status
            $retirement->imprestRequest()->update(['status' => 'disbursed']);
            
            $retirement->delete();

            return redirect()->route('imprest.requests.show', $retirement->imprest_request_id)
                ->with('success', 'Retirement deleted successfully.');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete retirement.']);
        }
    }
}
