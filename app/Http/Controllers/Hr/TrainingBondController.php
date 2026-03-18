<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\TrainingBond;
use App\Models\Hr\TrainingProgram;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class TrainingBondController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $bonds = TrainingBond::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'trainingProgram'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($bonds)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($bond) {
                    return $bond->employee->full_name;
                })
                ->addColumn('employee_number', function ($bond) {
                    return $bond->employee->employee_number;
                })
                ->addColumn('program_name', function ($bond) {
                    return $bond->trainingProgram->program_name;
                })
                ->addColumn('bond_amount_display', function ($bond) {
                    return number_format($bond->bond_amount, 2);
                })
                ->addColumn('bond_period_display', function ($bond) {
                    return $bond->bond_period_months . ' months';
                })
                ->addColumn('period', function ($bond) {
                    return $bond->start_date->format('d M Y') . ' - ' . $bond->end_date->format('d M Y');
                })
                ->addColumn('remaining_days', function ($bond) {
                    if ($bond->end_date <= now()) {
                        return '<span class="badge bg-danger">Expired</span>';
                    }
                    $days = $bond->remaining_days;
                    $badge = $days <= 90 ? 'warning' : 'success';
                    return '<span class="badge bg-' . $badge . '">' . $days . ' days</span>';
                })
                ->addColumn('status_badge', function ($bond) {
                    $badges = [
                        'active' => 'primary',
                        'fulfilled' => 'success',
                        'recovered' => 'danger',
                    ];
                    $badge = $badges[$bond->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($bond->status) . '</span>';
                })
                ->addColumn('action', function ($bond) {
                    $viewBtn = '<a href="' . route('hr.training-bonds.show', $bond->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.training-bonds.edit', $bond->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $bond->id . '"><i class="bx bx-trash"></i></button>';
                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['remaining_days', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.training.bonds.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $programs = TrainingProgram::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('program_name')
            ->get();

        $programId = $request->get('program_id');
        $employeeId = $request->get('employee_id');

        return view('hr-payroll.training.bonds.create', compact(
            'employees', 'programs', 'programId', 'employeeId'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'training_program_id' => 'required|exists:hr_training_programs,id',
            'bond_amount' => 'required|numeric|min:0',
            'bond_period_months' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'recovery_rules' => 'nullable|array',
            'status' => 'required|in:active,fulfilled,recovered',
        ]);

        DB::beginTransaction();
        try {
            // Verify employee belongs to company
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Calculate end date from start date and period if not provided
            if (empty($validated['end_date']) && !empty($validated['start_date']) && !empty($validated['bond_period_months'])) {
                $startDate = Carbon::parse($validated['start_date']);
                $validated['end_date'] = $startDate->copy()->addMonths($validated['bond_period_months'])->format('Y-m-d');
            }

            TrainingBond::create($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Training bond created successfully.'
                ]);
            }

            return redirect()->route('hr.training-bonds.index')
                ->with('success', 'Training bond created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create training bond: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TrainingBond $trainingBond)
    {
        if ($trainingBond->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $trainingBond->load(['employee', 'trainingProgram']);
        return view('hr-payroll.training.bonds.show', compact('trainingBond'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TrainingBond $trainingBond)
    {
        if ($trainingBond->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $programs = TrainingProgram::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('program_name')
            ->get();

        return view('hr-payroll.training.bonds.edit', compact(
            'trainingBond', 'employees', 'programs'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrainingBond $trainingBond)
    {
        if ($trainingBond->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'training_program_id' => 'required|exists:hr_training_programs,id',
            'bond_amount' => 'required|numeric|min:0',
            'bond_period_months' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'recovery_rules' => 'nullable|array',
            'status' => 'required|in:active,fulfilled,recovered',
        ]);

        DB::beginTransaction();
        try {
            // Verify employee belongs to company
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            $trainingBond->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Training bond updated successfully.'
                ]);
            }

            return redirect()->route('hr.training-bonds.index')
                ->with('success', 'Training bond updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update training bond: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TrainingBond $trainingBond)
    {
        if ($trainingBond->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $trainingBond->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Training bond deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete training bond: ' . $e->getMessage()
            ], 500);
        }
    }
}
