<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Allowance;
use App\Models\Hr\Employee;
use App\Models\Hr\AllowanceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class AllowanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allowances = Allowance::where('company_id', Auth::user()->company_id)
            ->with(['employee', 'allowanceType'])
            ->orderBy('date', 'desc')
            ->paginate(15);

        return view('hr-payroll.allowances.index', compact('allowances'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::where('company_id', Auth::user()->company_id)
            ->orderBy('first_name')
            ->get();

        $allowanceTypes = AllowanceType::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.allowances.create', compact('employees', 'allowanceTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'allowance_type_id' => 'required|exists:hr_allowance_types,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Verify employee belongs to company
        $employee = Employee::where('company_id', Auth::user()->company_id)
            ->findOrFail($request->employee_id);

        // Verify allowance type belongs to company
        $allowanceType = AllowanceType::where('company_id', Auth::user()->company_id)
            ->findOrFail($request->allowance_type_id);

        Allowance::create([
            'company_id' => Auth::user()->company_id,
            'employee_id' => $request->employee_id,
            'allowance_type_id' => $request->allowance_type_id,
            'date' => $request->date,
            'amount' => $request->amount,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('hr.allowances.index')
            ->with('success', 'Allowance created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $allowance = Allowance::where('company_id', Auth::user()->company_id)
            ->with(['employee', 'allowanceType'])
            ->findOrFail($id);

        return view('hr-payroll.allowances.show', compact('allowance'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $allowance = Allowance::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $employees = Employee::where('company_id', Auth::user()->company_id)
            ->orderBy('first_name')
            ->get();

        $allowanceTypes = AllowanceType::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.allowances.edit', compact('allowance', 'employees', 'allowanceTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $allowance = Allowance::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'allowance_type_id' => 'required|exists:hr_allowance_types,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Verify employee belongs to company
        $employee = Employee::where('company_id', Auth::user()->company_id)
            ->findOrFail($request->employee_id);

        // Verify allowance type belongs to company
        $allowanceType = AllowanceType::where('company_id', Auth::user()->company_id)
            ->findOrFail($request->allowance_type_id);

        $allowance->update([
            'employee_id' => $request->employee_id,
            'allowance_type_id' => $request->allowance_type_id,
            'date' => $request->date,
            'amount' => $request->amount,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('hr.allowances.index')
            ->with('success', 'Allowance updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $allowance = Allowance::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $allowance->delete();

        return redirect()->route('hr.allowances.index')
            ->with('success', 'Allowance deleted successfully.');
    }
}
