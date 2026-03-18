<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\AppraisalCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class AppraisalCycleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $cycles = AppraisalCycle::where('company_id', current_company_id())
                ->orderBy('start_date', 'desc');

            return DataTables::of($cycles)
                ->addIndexColumn()
                ->addColumn('cycle_type_badge', function ($cycle) {
                    $badges = [
                        'annual' => 'primary',
                        'semi_annual' => 'info',
                        'quarterly' => 'success',
                        'probation' => 'warning',
                    ];
                    $badge = $badges[$cycle->cycle_type] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $cycle->cycle_type)) . '</span>';
                })
                ->addColumn('period', function ($cycle) {
                    return $cycle->start_date->format('d M Y') . ' - ' . $cycle->end_date->format('d M Y');
                })
                ->addColumn('status_badge', function ($cycle) {
                    $badges = [
                        'draft' => 'secondary',
                        'active' => 'success',
                        'completed' => 'primary',
                        'cancelled' => 'danger',
                    ];
                    $badge = $badges[$cycle->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($cycle->status) . '</span>';
                })
                ->addColumn('appraisals_count', function ($cycle) {
                    return $cycle->appraisals()->count();
                })
                ->addColumn('action', function ($cycle) {
                    $viewBtn = '<a href="' . route('hr.appraisal-cycles.show', $cycle->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.appraisal-cycles.edit', $cycle->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $cycle->id . '" data-name="' . $cycle->cycle_name . '"><i class="bx bx-trash"></i></button>';
                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['cycle_type_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.performance.appraisal-cycles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.performance.appraisal-cycles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cycle_name' => 'required|string|max:200',
            'cycle_type' => 'required|in:annual,semi_annual,quarterly,probation',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:draft,active,completed,cancelled',
        ]);

        DB::beginTransaction();
        try {
            AppraisalCycle::create(array_merge($validated, [
                'company_id' => current_company_id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appraisal cycle created successfully.'
                ]);
            }

            return redirect()->route('hr.appraisal-cycles.index')
                ->with('success', 'Appraisal cycle created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create appraisal cycle: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AppraisalCycle $appraisalCycle)
    {
        if ($appraisalCycle->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $appraisalCycle->load(['appraisals.employee', 'company']);
        return view('hr-payroll.performance.appraisal-cycles.show', compact('appraisalCycle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AppraisalCycle $appraisalCycle)
    {
        if ($appraisalCycle->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('hr-payroll.performance.appraisal-cycles.edit', compact('appraisalCycle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AppraisalCycle $appraisalCycle)
    {
        if ($appraisalCycle->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'cycle_name' => 'required|string|max:200',
            'cycle_type' => 'required|in:annual,semi_annual,quarterly,probation',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:draft,active,completed,cancelled',
        ]);

        DB::beginTransaction();
        try {
            $appraisalCycle->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appraisal cycle updated successfully.'
                ]);
            }

            return redirect()->route('hr.appraisal-cycles.index')
                ->with('success', 'Appraisal cycle updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update appraisal cycle: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AppraisalCycle $appraisalCycle)
    {
        if ($appraisalCycle->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            // Check if cycle has appraisals
            if ($appraisalCycle->appraisals()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete appraisal cycle. It contains appraisals.'
                ], 422);
            }

            $appraisalCycle->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Appraisal cycle deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete appraisal cycle: ' . $e->getMessage()
            ], 500);
        }
    }
}
