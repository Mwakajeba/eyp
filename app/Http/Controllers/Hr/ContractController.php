<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Contract;
use App\Models\Hr\Employee;
use App\Models\Hr\ContractAttachment;
use App\Models\Hr\Position;
use App\Services\Hr\EmployeeService;
use App\Services\Hr\PositionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ContractController extends Controller
{
    protected $employeeService;
    protected $positionService;

    public function __construct(EmployeeService $employeeService, PositionService $positionService)
    {
        $this->employeeService = $employeeService;
        $this->positionService = $positionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $contracts = Contract::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with('employee')
            ->orderBy('hr_contracts.start_date', 'desc');

            return DataTables::of($contracts)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($contract) {
                    return $contract->employee->full_name;
                })
                ->addColumn('employee_number', function ($contract) {
                    return $contract->employee->employee_number;
                })
                ->addColumn('status_badge', function ($contract) {
                    $badges = [
                        'active' => 'success',
                        'expired' => 'warning',
                        'terminated' => 'danger',
                    ];
                    $badge = $badges[$contract->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . strtoupper($contract->status) . '</span>';
                })
                ->addColumn('action', function ($contract) {
                    $viewBtn = '<a href="' . route('hr.contracts.show', $contract->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.contracts.edit', $contract->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        // Dashboard statistics
        $today = now();
        $companyId = current_company_id();
        
        $stats = [
            'total' => Contract::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->count(),
            'active' => Contract::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
                ->where('start_date', '<=', $today)
                ->where(function($q) use ($today) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                })
                ->where('status', 'active')
                ->count(),
            'expiring_soon' => Contract::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
                ->whereNotNull('end_date')
                ->where('end_date', '>=', $today)
                ->where('end_date', '<=', $today->copy()->addDays(30))
                ->where('status', 'active')
                ->count(),
            'expired' => Contract::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
                ->where('status', 'expired')
                ->count(),
        ];

        return view('hr-payroll.contracts.index', compact('stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $employeeId = $request->employee_id;
        $employee = null;
        
        if ($employeeId) {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($employeeId);
        }

        // Get all employees for Select2 dropdown
        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('hr-payroll.contracts.create', compact('employee', 'employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'contract_type' => 'required|in:permanent,fixed_term,probation,contractor,intern',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'working_hours_per_week' => 'nullable|integer|min:1|max:168',
            'salary' => 'nullable|numeric|min:0',
            'renewal_flag' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::where('company_id', current_company_id())
                ->with('position.grade')
                ->findOrFail($validated['employee_id']);

            // Validate salary against position's grade if salary is provided and employee has a position
            if (!empty($validated['salary']) && $employee->position && $employee->position->grade) {
                $validation = $this->positionService->validateSalaryAgainstGrade(
                    $employee->position->grade_id,
                    $validated['salary']
                );
                
                if (!$validation['valid']) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['salary' => $validation['message']]);
                }
            }

            $contract = $this->employeeService->createContract($employee, $validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract created successfully.',
                    'redirect_url' => route('hr.employees.show', $employee->hash_id)
                ]);
            }

            return redirect()->route('hr.employees.show', $employee->hash_id)
                ->with('success', 'Contract created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create contract: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Contract $contract)
    {
        if ($contract->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $contract->load(['employee', 'amendments.approvedBy', 'attachments.uploader']);

        return view('hr-payroll.contracts.show', compact('contract'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contract $contract)
    {
        if ($contract->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('hr-payroll.contracts.edit', compact('contract'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contract $contract)
    {
        if ($contract->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'contract_type' => 'required|in:permanent,fixed_term,probation,contractor,intern',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'working_hours_per_week' => 'nullable|integer|min:1|max:168',
            'salary' => 'nullable|numeric|min:0',
            'renewal_flag' => 'boolean',
            'status' => 'required|in:active,expired,terminated',
        ]);

        // Validate salary against position's grade if salary is provided and employee has a position
        if (!empty($validated['salary'])) {
            $contract->load('employee.position.grade');
            if ($contract->employee->position && $contract->employee->position->grade) {
                $validation = $this->positionService->validateSalaryAgainstGrade(
                    $contract->employee->position->grade_id,
                    $validated['salary']
                );
                
                if (!$validation['valid']) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['salary' => $validation['message']]);
                }
            }
        }

        DB::beginTransaction();
        try {
            $contract->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract updated successfully.'
                ]);
            }

            return redirect()->route('hr.contracts.show', $contract->id)
                ->with('success', 'Contract updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update contract: ' . $e->getMessage()]);
        }
    }

    /**
     * Store contract attachment
     */
    public function storeAttachment(Request $request, Contract $contract)
    {
        if ($contract->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'document_type' => 'required|in:signed_contract,amendment,renewal,termination,other',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max
            'description' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('hr_contract_attachments', $fileName, 'public');

            $attachment = \App\Models\Hr\ContractAttachment::create([
                'contract_id' => $contract->id,
                'file_name' => $fileName,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'document_type' => $validated['document_type'],
                'description' => $validated['description'] ?? null,
                'uploaded_by' => auth()->id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document uploaded successfully.',
                    'attachment' => $attachment
                ]);
            }

            return redirect()->route('hr.contracts.show', $contract->id)
                ->with('success', 'Document uploaded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to upload document: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete contract attachment
     */
    public function deleteAttachment(Request $request, Contract $contract, \App\Models\Hr\ContractAttachment $attachment)
    {
        if ($contract->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($attachment->contract_id !== $contract->id) {
            abort(404, 'Attachment not found for this contract.');
        }

        $attachment->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.'
            ]);
        }

        return redirect()->route('hr.contracts.show', $contract->id)
            ->with('success', 'Document deleted successfully.');
    }
}
