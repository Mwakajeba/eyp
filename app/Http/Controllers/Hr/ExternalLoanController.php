<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\ExternalLoan;
use App\Models\Hr\Employee;
use App\Models\Hr\ExternalLoanInstitution;
use App\Models\PayrollEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use Carbon\Carbon;

class ExternalLoanController extends Controller
{
    public function index()
    {
        $loans = ExternalLoan::where('company_id', Auth::user()->company_id)
            ->with('employee')
            ->orderByDesc('date')
            ->get();

        return view('hr-payroll.external-loans.index', compact('loans'));
    }

    public function create()
    {
        $employees = Employee::where('company_id', Auth::user()->company_id)
            ->orderBy('first_name')
            ->get();

        $institutions = ExternalLoanInstitution::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.external-loans.create', compact('employees', 'institutions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'institution_name' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'total_loan' => 'required|numeric|min:0',
            'monthly_deduction' => 'required|numeric|min:0',
            'deduction_type' => 'required|in:fixed,percentage',
            'date_end_of_loan' => 'nullable|date',
            'date' => 'required|date',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Ensure employee belongs to company
        $employee = Employee::where('company_id', Auth::user()->company_id)
            ->findOrFail($request->employee_id);

        ExternalLoan::create([
            'company_id' => Auth::user()->company_id,
            'employee_id' => $request->employee_id,
            'institution_name' => $request->institution_name,
            'reference_number' => $request->reference_number,
            'total_loan' => $request->total_loan,
            'monthly_deduction' => $request->monthly_deduction,
            'deduction_type' => $request->deduction_type,
            'date_end_of_loan' => $request->date_end_of_loan,
            'date' => $request->date,
            'is_active' => $request->boolean('is_active'),
            'description' => $request->description,
        ]);

        return redirect()->route('hr.external-loans.index')
            ->with('success', 'External loan created successfully.');
    }

    public function edit(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_if(!$id, 404);

        $loan = ExternalLoan::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $employees = Employee::where('company_id', Auth::user()->company_id)
            ->orderBy('first_name')
            ->get();

        $institutions = ExternalLoanInstitution::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.external-loans.edit', compact('loan', 'employees', 'institutions'));
    }

    public function update(Request $request, string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_if(!$id, 404);

        $loan = ExternalLoan::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'institution_name' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'total_loan' => 'required|numeric|min:0',
            'monthly_deduction' => 'required|numeric|min:0',
            'deduction_type' => 'required|in:fixed,percentage',
            'date_end_of_loan' => 'nullable|date',
            'date' => 'required|date',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Ensure employee belongs to company
        $employee = Employee::where('company_id', Auth::user()->company_id)
            ->findOrFail($request->employee_id);

        $loan->update([
            'employee_id' => $request->employee_id,
            'institution_name' => $request->institution_name,
            'reference_number' => $request->reference_number,
            'total_loan' => $request->total_loan,
            'monthly_deduction' => $request->monthly_deduction,
            'deduction_type' => $request->deduction_type,
            'date_end_of_loan' => $request->date_end_of_loan,
            'date' => $request->date,
            'is_active' => $request->boolean('is_active'),
            'description' => $request->description,
        ]);

        return redirect()->route('hr.external-loans.index')
            ->with('success', 'External loan updated successfully.');
    }

    public function destroy(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_if(!$id, 404);

        $loan = ExternalLoan::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $loan->delete();

        return redirect()->route('hr.external-loans.index')
            ->with('success', 'External loan deleted successfully.');
    }

    public function show(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_if(!$id, 404);

        $loan = ExternalLoan::where('company_id', Auth::user()->company_id)
            ->with('employee')
            ->findOrFail($id);

        // Get deduction history from payroll_employees
        // Since loans are aggregated, we'll show all payroll deductions for this employee
        // where loans > 0 and payroll period is >= loan date
        $deductionHistory = PayrollEmployee::where('payroll_employees.employee_id', $loan->employee_id)
            ->join('payrolls', 'payroll_employees.payroll_id', '=', 'payrolls.id')
            ->where('payrolls.year', '>=', $loan->date->year)
            ->where(function ($q) use ($loan) {
                $q->where('payrolls.year', '>', $loan->date->year)
                  ->orWhere(function ($q2) use ($loan) {
                      $q2->where('payrolls.year', $loan->date->year)
                         ->where('payrolls.month', '>=', $loan->date->month);
                  });
            })
            ->where(function ($q) use ($loan) {
                if ($loan->date_end_of_loan) {
                    $q->where('payrolls.year', '<=', $loan->date_end_of_loan->year)
                      ->where(function ($q2) use ($loan) {
                          $q2->where('payrolls.year', '<', $loan->date_end_of_loan->year)
                             ->orWhere(function ($q3) use ($loan) {
                                 $q3->where('payrolls.year', $loan->date_end_of_loan->year)
                                    ->where('payrolls.month', '<=', $loan->date_end_of_loan->month);
                             });
                      });
                }
            })
            ->where('payroll_employees.loans', '>', 0)
            ->select('payroll_employees.*')
            ->with(['payroll' => function ($query) {
                $query->select('id', 'year', 'month', 'status', 'reference');
            }])
            ->orderByDesc('payrolls.year')
            ->orderByDesc('payrolls.month')
            ->get();

        return view('hr-payroll.external-loans.show', compact('loan', 'deductionHistory'));
    }
}


