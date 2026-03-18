<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\HeslbLoan;
use App\Models\Hr\HeslbRepayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HeslbLoanController extends Controller
{
    /**
     * Display a listing of HESLB loans
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        if ($request->ajax()) {
            $loans = HeslbLoan::with(['employee'])
                ->where('company_id', $companyId)
                ->select('hr_heslb_loans.*');

            return datatables()->of($loans)
                ->addColumn('employee_name', function ($loan) {
                    return $loan->employee ? $loan->employee->full_name : 'N/A';
                })
                ->addColumn('employee_number', function ($loan) {
                    return $loan->employee ? $loan->employee->employee_number : 'N/A';
                })
                ->addColumn('status', function ($loan) {
                    if (!$loan->is_active) {
                        return '<span class="badge bg-secondary">Inactive</span>';
                    }
                    if ($loan->outstanding_balance <= 0) {
                        return '<span class="badge bg-success">Paid Off</span>';
                    }
                    return '<span class="badge bg-primary">Active</span>';
                })
                ->addColumn('original_loan_amount', function ($loan) {
                    return number_format($loan->original_loan_amount, 2);
                })
                ->addColumn('outstanding_balance', function ($loan) {
                    return number_format($loan->outstanding_balance, 2);
                })
                ->addColumn('repayment_progress', function ($loan) {
                    if ($loan->original_loan_amount <= 0) {
                        return '0%';
                    }
                    $paid = $loan->original_loan_amount - $loan->outstanding_balance;
                    $percentage = ($paid / $loan->original_loan_amount) * 100;
                    return number_format($percentage, 1) . '%';
                })
                ->addColumn('action', function ($loan) {
                    $actions = '<div class="action-buttons">';
                    $actions .= '<a href="' . route('hr.heslb-loans.show', $loan->id) . '" class="btn btn-sm btn-info" title="View Details"><i class="bx bx-show"></i></a>';
                    $actions .= '<a href="' . route('hr.heslb-loans.edit', $loan->id) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        // Statistics
        $stats = [
            'total' => HeslbLoan::where('company_id', $companyId)->count(),
            'active' => HeslbLoan::where('company_id', $companyId)
                ->where('is_active', true)
                ->where('outstanding_balance', '>', 0)
                ->count(),
            'paid_off' => HeslbLoan::where('company_id', $companyId)
                ->where(function ($q) {
                    $q->where('outstanding_balance', '<=', 0)
                      ->orWhere('is_active', false);
                })
                ->count(),
            'total_outstanding' => HeslbLoan::where('company_id', $companyId)
                ->where('is_active', true)
                ->sum('outstanding_balance'),
        ];

        return view('hr-payroll.heslb-loans.index', compact('stats'));
    }

    /**
     * Show the form for creating a new HESLB loan
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Get employees for the current company
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('hr-payroll.heslb-loans.create', compact('employees'));
    }

    /**
     * Store a newly created HESLB loan
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'loan_number' => 'nullable|string|max:255',
            'original_loan_amount' => 'required|numeric|min:0.01',
            'outstanding_balance' => 'required|numeric|min:0',
            'loan_start_date' => 'required|date',
            'loan_end_date' => 'nullable|date|after_or_equal:loan_start_date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Ensure outstanding balance doesn't exceed original amount
        if ($request->outstanding_balance > $request->original_loan_amount) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['outstanding_balance' => 'Outstanding balance cannot exceed original loan amount.']);
        }

        $user = Auth::user();

        HeslbLoan::create([
            'company_id' => $user->company_id,
            'employee_id' => $request->employee_id,
            'loan_number' => $request->loan_number,
            'original_loan_amount' => $request->original_loan_amount,
            'outstanding_balance' => $request->outstanding_balance,
            'deduction_percent' => $request->deduction_percent,
            'loan_start_date' => $request->loan_start_date,
            'loan_end_date' => $request->loan_end_date,
            'is_active' => $request->has('is_active') ? true : false,
            'notes' => $request->notes,
        ]);

        return redirect()->route('hr.heslb-loans.index')
            ->with('success', 'HESLB loan created successfully.');
    }

    /**
     * Display the specified HESLB loan
     */
    public function show($id)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $loan = HeslbLoan::with(['employee', 'repayments.payroll'])
            ->where('company_id', $companyId)
            ->findOrFail($id);
        
        // Order repayments by date descending
        $loan->repayments = $loan->repayments->sortByDesc('repayment_date')->values();

        // Calculate statistics
        $totalRepaid = $loan->original_loan_amount - $loan->outstanding_balance;
        $repaymentPercentage = $loan->original_loan_amount > 0 
            ? ($totalRepaid / $loan->original_loan_amount) * 100 
            : 0;

        return view('hr-payroll.heslb-loans.show', compact('loan', 'totalRepaid', 'repaymentPercentage'));
    }

    /**
     * Show the form for editing the specified HESLB loan
     */
    public function edit($id)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $loan = HeslbLoan::where('company_id', $companyId)->findOrFail($id);

        // Get employees for the current company
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('hr-payroll.heslb-loans.edit', compact('loan', 'employees'));
    }

    /**
     * Update the specified HESLB loan
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $loan = HeslbLoan::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'loan_number' => 'nullable|string|max:255',
            'original_loan_amount' => 'required|numeric|min:0.01',
            'outstanding_balance' => 'required|numeric|min:0',
            'deduction_percent' => 'nullable|numeric|min:0|max:100',
            'loan_start_date' => 'required|date',
            'loan_end_date' => 'nullable|date|after_or_equal:loan_start_date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Ensure outstanding balance doesn't exceed original amount
        if ($request->outstanding_balance > $request->original_loan_amount) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['outstanding_balance' => 'Outstanding balance cannot exceed original loan amount.']);
        }

        $loan->update([
            'employee_id' => $request->employee_id,
            'loan_number' => $request->loan_number,
            'original_loan_amount' => $request->original_loan_amount,
            'outstanding_balance' => $request->outstanding_balance,
            'deduction_percent' => $request->deduction_percent,
            'loan_start_date' => $request->loan_start_date,
            'loan_end_date' => $request->loan_end_date,
            'is_active' => $request->has('is_active') ? true : false,
            'notes' => $request->notes,
        ]);

        return redirect()->route('hr.heslb-loans.show', $loan->id)
            ->with('success', 'HESLB loan updated successfully.');
    }

    /**
     * Remove the specified HESLB loan
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $loan = HeslbLoan::where('company_id', $companyId)->findOrFail($id);

        // Check if there are repayments
        if ($loan->repayments()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete loan with repayment history. Please deactivate instead.');
        }

        $loan->delete();

        return redirect()->route('hr.heslb-loans.index')
            ->with('success', 'HESLB loan deleted successfully.');
    }
}

