<?php

namespace App\Http\Controllers;

use App\Models\StoreRequisition;
use App\Models\StoreRequisitionItem;
use App\Models\StoreRequisitionApprovalSettings;
use App\Models\Inventory\Item;
use App\Models\Department;
use App\Models\User;
use App\Models\Branch;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use DataTables;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;

class StoreRequisitionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the store requisition management dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Get statistics for dashboard cards
        $stats = [
            'pending_requisitions' => StoreRequisition::where('company_id', $companyId)->where('status', 'pending')->count(),
            'approved_requisitions' => StoreRequisition::where('company_id', $companyId)->where('status', 'approved')->count(),
            'rejected_requisitions' => StoreRequisition::where('company_id', $companyId)->where('status', 'rejected')->count(),
            'issued_requisitions' => \App\Models\StoreIssue::where('company_id', $companyId)->count(),
            'returned_requisitions' => \App\Models\StoreRequisitionReturn::where('company_id', $companyId)->count(),
            'completed_requisitions' => StoreRequisition::where('company_id', $companyId)->whereIn('status', ['completed', 'fully_issued'])->count(),
            'cancelled_requisitions' => StoreRequisition::where('company_id', $companyId)->where('status', 'cancelled')->count(),
            'total_requisitions' => StoreRequisition::where('company_id', $companyId)->count(),
        ];

        // Get approval settings for modal
        $branchId = $user->branch_id;
        $approvalSettings = StoreRequisitionApprovalSettings::where('company_id', $companyId)->first();

        return view('store_requisitions.index', compact('stats', 'approvalSettings'));
    }

    /**
     * Show all store requisitions (CRUD page)
     */
    public function requisitions(Request $request)
    {
        if ($request->ajax()) {
            return $this->getRequisitionsDataTable($request);
        }

        $departments = Department::where('company_id', Auth::user()->company_id)->get();
        
        return view('store_requisitions.requisitions.index', compact('departments'));
    }

    /**
     * DataTable for store requisitions
     */
    private function getRequisitionsDataTable(Request $request)
    {
        $query = StoreRequisition::with(['requestedBy', 'branch'])
            ->where('company_id', Auth::user()->company_id);

        // Apply filters
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('branch') && $request->branch != '') {
            $query->where('department_id', $request->branch);
        }

        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('action', function ($requisition) {
                $actions = '<div class="btn-group" role="group">';
                
                $actions .= '<a href="' . route('store-requisitions.requisitions.show', $requisition->hash_id) . '" class="btn btn-sm btn-info" title="View Details">
                    <i class="bx bx-show"></i>
                </a>';

                if ($requisition->status === 'pending') {
                    $actions .= '<a href="' . route('store-requisitions.requisitions.edit', $requisition->hash_id) . '" class="btn btn-sm btn-warning" title="Edit">
                        <i class="bx bx-edit"></i>
                    </a>';
                }

                $actions .= '</div>';
                return $actions;
            })
            ->addColumn('voucher_no', function ($requisition) {
                return $requisition->requisition_number;
            })
            ->addColumn('status_badge', function ($requisition) {
                $badges = [
                    'pending' => '<span class="badge bg-warning">Pending</span>',
                    'approved' => '<span class="badge bg-success">Approved</span>',
                    'rejected' => '<span class="badge bg-danger">Rejected</span>',
                    'partially_issued' => '<span class="badge bg-info">Partially Issued</span>',
                    'fully_issued' => '<span class="badge bg-primary">Fully Issued</span>',
                    'completed' => '<span class="badge bg-success">Completed</span>',
                    'cancelled' => '<span class="badge bg-secondary">Cancelled</span>',
                ];
                return $badges[$requisition->status] ?? '<span class="badge bg-secondary">' . ucfirst($requisition->status) . '</span>';
            })
            ->addColumn('employee_name', function ($requisition) {
                return $requisition->requestedBy ? $requisition->requestedBy->name : 'N/A';
            })
            ->addColumn('branch_name', function ($requisition) {
                return $requisition->branch ? $requisition->branch->name : 'N/A';
            })
            ->addColumn('request_date', function ($requisition) {
                return $requisition->required_date ? $requisition->required_date->format('Y-m-d') : '';
            })
            ->editColumn('created_at', function ($requisition) {
                return $requisition->created_at->format('Y-m-d H:i:s');
            })
            ->rawColumns(['action', 'status_badge'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = User::where('company_id', Auth::user()->company_id)->get();
        //$employees = Auth::user();
        $products = Item::where('company_id', Auth::user()->company_id)->where('is_active', true)->get();
        
        return view('store_requisitions.requisitions.create', compact('employees', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Debug: Log the incoming request data
        \Log::info('Store Requisition Request Data:', $request->all());

        try {
            $request->validate([
                'employee_id' => 'required|exists:users,id',
                'request_date' => 'required|date',
                'purpose' => 'required|string|max:500',
                'description' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:inventory_items,id',
                'items.*.quantity_requested' => 'required|numeric|min:0.01',
                'items.*.item_notes' => 'nullable|string',
            ], [
                'items.required' => 'At least one requisition item is required.',
                'items.min' => 'At least one requisition item is required.',
                'items.*.product_id.required' => 'Product is required for each item.',
                'items.*.product_id.exists' => 'Selected product is invalid.',
                'items.*.quantity_requested.required' => 'Quantity is required for each item.',
                'items.*.quantity_requested.min' => 'Quantity must be greater than 0.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Store Requisition Validation Error:', $e->errors());
            
            // If AJAX request, return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed. Please check all required fields.',
                    'errors' => $e->errors()
                ], 422);
            }
            
            // Otherwise redirect back with errors
            return back()->withErrors($e->errors())->withInput();
        }

        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? 1); // Default to 1 if no branch
        
        try {
            DB::beginTransaction();

            // Get user and assign department if available
            $employee = User::find($request->employee_id);
            $employeeData = Employee::where('user_id', $request->employee_id)->first();
            if (!$employee) {
                throw new \Exception('User not found');
            }
            
            // Get department from employee if assigned
            $departmentId = $employeeData && $employeeData->department_id ? $employeeData->department_id : null;

            // Generate voucher number
            $voucherNo = $this->generateVoucherNumber($user->company_id, $branchId);

            // Create store requisition
            $requisition = StoreRequisition::create([
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'requested_by' => $request->employee_id,
                'requisition_number' => $voucherNo,
                'required_date' => $request->request_date,
                'notes' => $request->description,
                'purpose' => $request->purpose,
                'status' => 'pending',
                'current_approval_level' => 1,
            ]);

            \Log::info('Store Requisition Created:', $requisition->toArray());

            // Create store requisition items
            foreach ($request->items as $itemData) {
                $product = Item::find($itemData['product_id']);
                
                $item = StoreRequisitionItem::create([
                    'store_requisition_id' => $requisition->id,
                    'inventory_item_id' => $itemData['product_id'],
                    'quantity_requested' => $itemData['quantity_requested'],
                    'quantity_approved' => 0,
                    'quantity_issued' => 0,
                    'quantity_returned' => 0,
                    'unit_cost' => $product->unit_cost ?? 0,
                    'unit_of_measure' => $product->unit_of_measure ?? '',
                    'item_notes' => $itemData['item_notes'] ?? '',
                    'status' => 'pending',
                ]);

                \Log::info('Store Requisition Item Created:', $item->toArray());
            }

            // Initialize approval workflow
            $this->initializeApprovalWorkflow($requisition);

            DB::commit();

            $successMessage = 'Store requisition created successfully. Voucher No: ' . $voucherNo;
            
            // If AJAX request, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'redirect' => route('store-requisitions.requisitions.show', $requisition->hash_id)
                ], 200);
            }

            return redirect()->route('store-requisitions.requisitions.show', $requisition->hash_id)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Store Requisition Creation Failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            $errorMessage = 'Failed to create store requisition. Error: ' . $e->getMessage();
            
            // If AJAX request, return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return back()->withInput()->withErrors(['error' => $errorMessage]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(StoreRequisition $storeRequisition)
    {
        $storeRequisition->load(['requestedBy', 'branch', 'items.product', 'approvals.approver']);
        
        // Check if user can approve this requisition
        $canApprove = $this->canUserApprove($storeRequisition, Auth::user());
        
        return view('store_requisitions.requisitions.show', compact('storeRequisition', 'canApprove'));
    }

    /**
     * Print/Export requisition as PDF
     */
    public function print(StoreRequisition $storeRequisition)
    {
        $storeRequisition->load(['requestedBy', 'branch', 'items.product', 'approvals.approver']);
        
        $branch = $storeRequisition->branch;
        
        $pdf = \PDF::loadView('store_requisitions.requisitions.print', compact('storeRequisition', 'branch'));
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download("Requisition-{$storeRequisition->requisition_number}.pdf");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StoreRequisition $storeRequisition)
    {
        if ($storeRequisition->status !== 'pending') {
            return redirect()->route('store-requisitions.requisitions.show', $storeRequisition->hash_id)
                ->with('error', 'Only pending requisitions can be edited.');
        }

        $employees = User::where('company_id', Auth::user()->company_id)->get();
        $products = Item::where('company_id', Auth::user()->company_id)->where('is_active', true)->get();
        
        $storeRequisition->load('items');
        
        return view('store_requisitions.requisitions.edit', compact('storeRequisition', 'employees', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StoreRequisition $storeRequisition)
    {
        if ($storeRequisition->status !== 'pending') {
            return redirect()->route('store-requisitions.requisitions.show', $storeRequisition->hash_id)
                ->with('error', 'Only pending requisitions can be updated.');
        }

        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'request_date' => 'required|date',
            'purpose' => 'required|string|max:500',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:inventory_items,id',
            'items.*.quantity_requested' => 'required|numeric|min:0.01',
            'items.*.item_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Get user and assign default department for now
            $employee = User::find($request->employee_id);
            $employeeData = Employee::where('user_id', $request->employee_id)->first();
            if (!$employee) {
                throw new \Exception('User not found');
            }
            
            // Get department from employee if assigned
            $user = Auth::user();
            $departmentId = $employeeData && $employeeData->department_id ? $employeeData->department_id : null;

            // Update store requisition
            $storeRequisition->update([
                'requested_by' => $request->employee_id,
                'department_id' => $departmentId,
                'required_date' => $request->request_date,
                'notes' => $request->description,
                'purpose' => $request->purpose,
            ]);

            // Delete existing items and create new ones
            $storeRequisition->items()->delete();

            foreach ($request->items as $item) {
                $product = Item::find($item['product_id']);
                
                StoreRequisitionItem::create([
                    'store_requisition_id' => $storeRequisition->id,
                    'inventory_item_id' => $item['product_id'],
                    'quantity_requested' => $item['quantity_requested'],
                    'quantity_approved' => 0,
                    'quantity_issued' => 0,
                    'quantity_returned' => 0,
                    'unit_cost' => $product->unit_cost ?? 0,
                    'unit_of_measure' => $product->unit_of_measure ?? '',
                    'item_notes' => $item['item_notes'] ?? '',
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return redirect()->route('store-requisitions.requisitions.show', $storeRequisition->hash_id)
                ->with('success', 'Store requisition updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Store Requisition Update Failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to update store requisition. Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StoreRequisition $storeRequisition)
    {
        if ($storeRequisition->status !== 'pending') {
            return response()->json(['error' => 'Only pending requisitions can be deleted.'], 400);
        }

        try {
            DB::beginTransaction();

            $storeRequisition->items()->delete();
            $storeRequisition->approvals()->delete();
            $storeRequisition->delete();

            DB::commit();

            return response()->json(['success' => 'Store requisition deleted successfully.']);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Store Requisition Deletion Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete store requisition.'], 500);
        }
    }

    /**
     * Generate voucher number for store requisition
     */
    private function generateVoucherNumber($companyId, $branchId)
    {
        $prefix = 'SR-' . date('Y') . '-';
        $lastRequisition = StoreRequisition::where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('requisition_number', 'like', $prefix . '%')
            ->orderBy('requisition_number', 'desc')
            ->first();

        if ($lastRequisition) {
            $lastNumber = (int) substr($lastRequisition->requisition_number, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Initialize approval workflow for store requisition
     */
    private function initializeApprovalWorkflow(StoreRequisition $requisition)
    {
        $approvalSettings = StoreRequisitionApprovalSettings::where('company_id', $requisition->company_id)->first();
        
        if (!$approvalSettings) {
            // Auto-approve if no approval settings
            $requisition->update([
                'status' => 'approved',
                'current_approval_level' => 0
            ]);
            return;
        }

        // Find first enabled approval level
        for ($level = 1; $level <= 5; $level++) {
            if ($approvalSettings->{"level_{$level}_enabled"}) {
                $requisition->update(['current_approval_level' => $level]);
                break;
            }
        }
    }

    /**
     * Check if user can approve the requisition
     */
    private function canUserApprove(StoreRequisition $requisition, $user)
    {
        if (!$requisition->canBeApproved()) {
            return false;
        }

        $approvalSettings = StoreRequisitionApprovalSettings::where('company_id', $requisition->company_id)->first();
        
        if (!$approvalSettings) {
            return false;
        }

        $currentLevel = $requisition->current_approval_level;
        
        // Check if user is assigned to current approval level
        $levelUserId = $approvalSettings->{"level_{$currentLevel}_user_id"};
        $levelRoleId = $approvalSettings->{"level_{$currentLevel}_role_id"};
        
        if ($levelUserId && $levelUserId == $user->id) {
            return true;
        }
        
        if ($levelRoleId && $user->hasRole($levelRoleId)) {
            return true;
        }
        
        return false;
    }

    /**
     * Get approved requisitions for issues
     */
    public function getApprovedRequisitions(Request $request)
    {
        $requisitions = StoreRequisition::where('company_id', Auth::user()->company_id)
            ->where('status', 'approved')
            ->with(['requestedBy:id,name,email', 'branch:id,name'])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'requisition_number', 'purpose', 'requested_by', 'branch_id', 'created_at']);

        return response()->json($requisitions);
    }

    /**
     * Get items for a specific requisition
     */
    public function getItems($requisitionId)
    {
        // Handle both numeric ID and hash ID
        if (is_numeric($requisitionId)) {
            $items = StoreRequisitionItem::where('store_requisition_id', $requisitionId)
                ->with(['product.category'])
                ->get();
        } else {
            // Handle hash ID - first resolve to numeric ID
            $requisition = StoreRequisition::where('company_id', Auth::user()->company_id)
                ->get()
                ->first(function ($item) use ($requisitionId) {
                    return $item->hash_id === $requisitionId;
                });
                
            if (!$requisition) {
                return response()->json(['error' => 'Requisition not found'], 404);
            }
            
            $items = StoreRequisitionItem::where('store_requisition_id', $requisition->id)
                ->with(['product.category'])
                ->get();
        }

        return response()->json($items);
    }
}
