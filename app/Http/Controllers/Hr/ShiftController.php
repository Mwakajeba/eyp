<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $shifts = Shift::where('company_id', current_company_id())
                ->orderBy('shift_code');

            return DataTables::of($shifts)
                ->addIndexColumn()
                ->addColumn('time_range', function ($shift) {
                    return date('H:i', strtotime($shift->start_time)) . ' - ' . date('H:i', strtotime($shift->end_time));
                })
                ->addColumn('duration', function ($shift) {
                    return number_format($shift->duration_hours, 2) . ' hrs';
                })
                ->addColumn('status_badge', function ($shift) {
                    $badge = $shift->is_active ? 'success' : 'secondary';
                    $text = $shift->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('action', function ($shift) {
                    $editBtn = '<a href="' . route('hr.shifts.edit', $shift->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $shift->id . '" data-name="' . $shift->shift_name . '"><i class="bx bx-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.shifts.index');
    }

    public function create()
    {
        return view('hr-payroll.shifts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hr_shifts')->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'shift_name' => 'required|string|max:200',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'crosses_midnight' => 'boolean',
            'shift_differential_percent' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            Shift::create(array_merge($validated, [
                'company_id' => current_company_id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Shift created successfully.'
                ]);
            }

            return redirect()->route('hr.shifts.index')
                ->with('success', 'Shift created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create shift: ' . $e->getMessage()]);
        }
    }

    public function edit(Shift $shift)
    {
        if ($shift->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('hr-payroll.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        if ($shift->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'shift_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hr_shifts')->ignore($shift->id)->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'shift_name' => 'required|string|max:200',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'crosses_midnight' => 'boolean',
            'shift_differential_percent' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $shift->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Shift updated successfully.'
                ]);
            }

            return redirect()->route('hr.shifts.index')
                ->with('success', 'Shift updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update shift: ' . $e->getMessage()]);
        }
    }

    public function destroy(Shift $shift)
    {
        if ($shift->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        // Check if shift has employee assignments
        $assignmentCount = $shift->employeeSchedules()->count();
        if ($assignmentCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete shift because it has ' . $assignmentCount . ' employee assignment(s).'
            ], 400);
        }

        $shift->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shift deleted successfully.'
        ]);
    }
}

