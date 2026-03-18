<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Kpi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class KpiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $kpis = Kpi::where('company_id', current_company_id())
                ->orderBy('kpi_code');

            return DataTables::of($kpis)
                ->addIndexColumn()
                ->addColumn('weight_display', function ($kpi) {
                    return $kpi->weight_percent ? number_format($kpi->weight_percent, 2) . '%' : '-';
                })
                ->addColumn('target_display', function ($kpi) {
                    return $kpi->target_value ? number_format($kpi->target_value, 2) : '-';
                })
                ->addColumn('scoring_method_badge', function ($kpi) {
                    $badge = $kpi->scoring_method === 'numeric' ? 'primary' : 'info';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($kpi->scoring_method) . '</span>';
                })
                ->addColumn('applicable_to_badge', function ($kpi) {
                    $badges = [
                        'company' => 'danger',
                        'department' => 'warning',
                        'position' => 'info',
                        'individual' => 'success',
                    ];
                    $badge = $badges[$kpi->applicable_to] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($kpi->applicable_to) . '</span>';
                })
                ->addColumn('status_badge', function ($kpi) {
                    $badge = $kpi->is_active ? 'success' : 'secondary';
                    $text = $kpi->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('action', function ($kpi) {
                    $editBtn = '<a href="' . route('hr.kpis.edit', $kpi->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $kpi->id . '" data-name="' . $kpi->kpi_name . '"><i class="bx bx-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['scoring_method_badge', 'applicable_to_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.performance.kpis.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.performance.kpis.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kpi_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hr_kpis')->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'kpi_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'measurement_criteria' => 'nullable|string',
            'weight_percent' => 'nullable|numeric|min:0|max:100',
            'target_value' => 'nullable|numeric|min:0',
            'scoring_method' => 'required|in:numeric,rating_scale',
            'applicable_to' => 'required|in:company,department,position,individual',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            Kpi::create(array_merge($validated, [
                'company_id' => current_company_id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'KPI created successfully.'
                ]);
            }

            return redirect()->route('hr.kpis.index')
                ->with('success', 'KPI created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create KPI: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Kpi $kpi)
    {
        if ($kpi->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $kpi->load('company');
        return view('hr-payroll.performance.kpis.show', compact('kpi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Kpi $kpi)
    {
        if ($kpi->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('hr-payroll.performance.kpis.edit', compact('kpi'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kpi $kpi)
    {
        if ($kpi->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'kpi_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hr_kpis')->ignore($kpi->id)->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'kpi_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'measurement_criteria' => 'nullable|string',
            'weight_percent' => 'nullable|numeric|min:0|max:100',
            'target_value' => 'nullable|numeric|min:0',
            'scoring_method' => 'required|in:numeric,rating_scale',
            'applicable_to' => 'required|in:company,department,position,individual',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $kpi->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'KPI updated successfully.'
                ]);
            }

            return redirect()->route('hr.kpis.index')
                ->with('success', 'KPI updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update KPI: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kpi $kpi)
    {
        if ($kpi->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            // Check if KPI is used in any appraisals
            if ($kpi->appraisalKpiScores()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete KPI. It is being used in appraisals.'
                ], 422);
            }

            $kpi->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'KPI deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete KPI: ' . $e->getMessage()
            ], 500);
        }
    }
}
