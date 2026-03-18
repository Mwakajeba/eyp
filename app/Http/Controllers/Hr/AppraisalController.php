<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Appraisal;
use App\Models\Hr\AppraisalCycle;
use App\Models\Hr\Employee;
use App\Models\Hr\Kpi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AppraisalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $appraisals = Appraisal::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'cycle', 'appraiser'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($appraisals)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($appraisal) {
                    return $appraisal->employee->full_name;
                })
                ->addColumn('employee_number', function ($appraisal) {
                    return $appraisal->employee->employee_number;
                })
                ->addColumn('cycle_name', function ($appraisal) {
                    return $appraisal->cycle->cycle_name;
                })
                ->addColumn('appraiser_name', function ($appraisal) {
                    return $appraisal->appraiser->name ?? 'N/A';
                })
                ->addColumn('final_score_display', function ($appraisal) {
                    return $appraisal->final_score ? number_format($appraisal->final_score, 2) : '-';
                })
                ->addColumn('rating_badge', function ($appraisal) {
                    if (!$appraisal->rating) {
                        return '-';
                    }
                    $badges = [
                        'excellent' => 'success',
                        'good' => 'primary',
                        'average' => 'warning',
                        'needs_improvement' => 'danger',
                    ];
                    $badge = $badges[$appraisal->rating] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($appraisal->rating) . '</span>';
                })
                ->addColumn('status_badge', function ($appraisal) {
                    $badges = [
                        'draft' => 'secondary',
                        'submitted' => 'info',
                        'approved' => 'success',
                        'locked' => 'dark',
                    ];
                    $badge = $badges[$appraisal->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($appraisal->status) . '</span>';
                })
                ->addColumn('action', function ($appraisal) {
                    $viewBtn = '<a href="' . route('hr.appraisals.show', $appraisal->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.appraisals.edit', $appraisal->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $appraisal->id . '"><i class="bx bx-trash"></i></button>';
                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['rating_badge', 'status_badge', 'action'])
                ->make(true);
        }

        // Dashboard statistics
        $companyId = current_company_id();
        $stats = [
            'total' => Appraisal::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->count(),
            'draft' => Appraisal::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->where('status', 'draft')->count(),
            'submitted' => Appraisal::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->where('status', 'submitted')->count(),
            'approved' => Appraisal::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->where('status', 'approved')->count(),
        ];

        return view('hr-payroll.performance.appraisals.index', compact('stats'));
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
        
        $cycles = AppraisalCycle::where('company_id', $companyId)
            ->where('status', AppraisalCycle::STATUS_ACTIVE)
            ->orderBy('start_date', 'desc')
            ->get();
        
        $kpis = Kpi::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('applicable_to', Kpi::APPLICABLE_INDIVIDUAL)
            ->orderBy('kpi_code')
            ->get();

        $appraisalCycleId = $request->get('cycle_id');
        $employeeId = $request->get('employee_id');

        return view('hr-payroll.performance.appraisals.create', compact(
            'employees', 'cycles', 'kpis', 'appraisalCycleId', 'employeeId'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'cycle_id' => 'required|exists:hr_appraisal_cycles,id',
            'appraiser_id' => 'required|exists:users,id',
            'self_assessment_score' => 'nullable|numeric|min:0|max:100',
            'manager_score' => 'nullable|numeric|min:0|max:100',
            'final_score' => 'nullable|numeric|min:0|max:100',
            'rating' => 'nullable|in:excellent,good,average,needs_improvement',
            'status' => 'required|in:draft,submitted,approved,locked',
            'kpi_scores' => 'nullable|array',
            'kpi_scores.*.kpi_id' => 'required|exists:hr_kpis,id',
            'kpi_scores.*.self_score' => 'nullable|numeric|min:0|max:100',
            'kpi_scores.*.manager_score' => 'nullable|numeric|min:0|max:100',
            'kpi_scores.*.final_score' => 'nullable|numeric|min:0|max:100',
            'kpi_scores.*.comments' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Verify employee belongs to company
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Check if appraisal already exists for this employee and cycle
            $existingAppraisal = Appraisal::where('employee_id', $validated['employee_id'])
                ->where('cycle_id', $validated['cycle_id'])
                ->first();

            if ($existingAppraisal) {
                return back()->withInput()->withErrors([
                    'error' => 'Appraisal already exists for this employee and cycle.'
                ]);
            }

            // Calculate final score if not provided
            if (empty($validated['final_score']) && !empty($validated['manager_score'])) {
                $validated['final_score'] = $validated['manager_score'];
            } elseif (empty($validated['final_score']) && !empty($validated['self_assessment_score'])) {
                $validated['final_score'] = $validated['self_assessment_score'];
            }

            // Determine rating if not provided but final score exists
            if (empty($validated['rating']) && !empty($validated['final_score'])) {
                $score = $validated['final_score'];
                if ($score >= 90) {
                    $validated['rating'] = 'excellent';
                } elseif ($score >= 75) {
                    $validated['rating'] = 'good';
                } elseif ($score >= 60) {
                    $validated['rating'] = 'average';
                } else {
                    $validated['rating'] = 'needs_improvement';
                }
            }

            $kpiScores = $validated['kpi_scores'] ?? [];
            unset($validated['kpi_scores']);

            $appraisal = Appraisal::create($validated);

            // Create KPI scores
            foreach ($kpiScores as $kpiScore) {
                if (empty($kpiScore['final_score']) && !empty($kpiScore['manager_score'])) {
                    $kpiScore['final_score'] = $kpiScore['manager_score'];
                } elseif (empty($kpiScore['final_score']) && !empty($kpiScore['self_score'])) {
                    $kpiScore['final_score'] = $kpiScore['self_score'];
                }

                $appraisal->kpiScores()->create([
                    'kpi_id' => $kpiScore['kpi_id'],
                    'self_score' => $kpiScore['self_score'] ?? null,
                    'manager_score' => $kpiScore['manager_score'] ?? null,
                    'final_score' => $kpiScore['final_score'] ?? null,
                    'comments' => $kpiScore['comments'] ?? null,
                ]);
            }

            // Recalculate final score from KPI scores if KPIs exist
            if ($appraisal->kpiScores()->exists()) {
                $finalScore = $appraisal->calculateFinalScore();
                $rating = $appraisal->determineRating();
                $appraisal->update([
                    'final_score' => $finalScore,
                    'rating' => $rating,
                ]);
            }

            if ($validated['status'] === 'approved') {
                $appraisal->update([
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appraisal created successfully.'
                ]);
            }

            return redirect()->route('hr.appraisals.index')
                ->with('success', 'Appraisal created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create appraisal: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Appraisal $appraisal)
    {
        if ($appraisal->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $appraisal->load(['employee', 'cycle', 'appraiser', 'approver', 'kpiScores.kpi']);
        return view('hr-payroll.performance.appraisals.show', compact('appraisal'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Appraisal $appraisal)
    {
        if ($appraisal->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($appraisal->status === Appraisal::STATUS_LOCKED) {
            return redirect()->route('hr.appraisals.show', $appraisal->id)
                ->with('error', 'Cannot edit locked appraisal.');
        }

        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $cycles = AppraisalCycle::where('company_id', $companyId)
            ->orderBy('start_date', 'desc')
            ->get();
        
        $kpis = Kpi::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('applicable_to', Kpi::APPLICABLE_INDIVIDUAL)
            ->orderBy('kpi_code')
            ->get();

        $appraisal->load(['kpiScores.kpi']);

        return view('hr-payroll.performance.appraisals.edit', compact(
            'appraisal', 'employees', 'cycles', 'kpis'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Appraisal $appraisal)
    {
        if ($appraisal->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($appraisal->status === Appraisal::STATUS_LOCKED) {
            return back()->withErrors(['error' => 'Cannot update locked appraisal.']);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'cycle_id' => 'required|exists:hr_appraisal_cycles,id',
            'appraiser_id' => 'required|exists:users,id',
            'self_assessment_score' => 'nullable|numeric|min:0|max:100',
            'manager_score' => 'nullable|numeric|min:0|max:100',
            'final_score' => 'nullable|numeric|min:0|max:100',
            'rating' => 'nullable|in:excellent,good,average,needs_improvement',
            'status' => 'required|in:draft,submitted,approved,locked',
            'kpi_scores' => 'nullable|array',
            'kpi_scores.*.id' => 'nullable|exists:hr_appraisal_kpi_scores,id',
            'kpi_scores.*.kpi_id' => 'required|exists:hr_kpis,id',
            'kpi_scores.*.self_score' => 'nullable|numeric|min:0|max:100',
            'kpi_scores.*.manager_score' => 'nullable|numeric|min:0|max:100',
            'kpi_scores.*.final_score' => 'nullable|numeric|min:0|max:100',
            'kpi_scores.*.comments' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Verify employee belongs to company
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Check if appraisal already exists for another employee/cycle combination
            if ($appraisal->employee_id != $validated['employee_id'] || $appraisal->cycle_id != $validated['cycle_id']) {
                $existingAppraisal = Appraisal::where('employee_id', $validated['employee_id'])
                    ->where('cycle_id', $validated['cycle_id'])
                    ->where('id', '!=', $appraisal->id)
                    ->first();

                if ($existingAppraisal) {
                    return back()->withInput()->withErrors([
                        'error' => 'Appraisal already exists for this employee and cycle.'
                    ]);
                }
            }

            // Calculate final score if not provided
            if (empty($validated['final_score']) && !empty($validated['manager_score'])) {
                $validated['final_score'] = $validated['manager_score'];
            } elseif (empty($validated['final_score']) && !empty($validated['self_assessment_score'])) {
                $validated['final_score'] = $validated['self_assessment_score'];
            }

            // Determine rating if not provided but final score exists
            if (empty($validated['rating']) && !empty($validated['final_score'])) {
                $score = $validated['final_score'];
                if ($score >= 90) {
                    $validated['rating'] = 'excellent';
                } elseif ($score >= 75) {
                    $validated['rating'] = 'good';
                } elseif ($score >= 60) {
                    $validated['rating'] = 'average';
                } else {
                    $validated['rating'] = 'needs_improvement';
                }
            }

            $kpiScores = $validated['kpi_scores'] ?? [];
            unset($validated['kpi_scores']);

            // Update appraisal
            $appraisal->update($validated);

            // Update or create KPI scores
            $existingKpiScoreIds = [];
            foreach ($kpiScores as $kpiScore) {
                if (empty($kpiScore['final_score']) && !empty($kpiScore['manager_score'])) {
                    $kpiScore['final_score'] = $kpiScore['manager_score'];
                } elseif (empty($kpiScore['final_score']) && !empty($kpiScore['self_score'])) {
                    $kpiScore['final_score'] = $kpiScore['self_score'];
                }

                if (!empty($kpiScore['id'])) {
                    // Update existing
                    $existingKpiScore = $appraisal->kpiScores()->find($kpiScore['id']);
                    if ($existingKpiScore) {
                        $existingKpiScore->update([
                            'kpi_id' => $kpiScore['kpi_id'],
                            'self_score' => $kpiScore['self_score'] ?? null,
                            'manager_score' => $kpiScore['manager_score'] ?? null,
                            'final_score' => $kpiScore['final_score'] ?? null,
                            'comments' => $kpiScore['comments'] ?? null,
                        ]);
                        $existingKpiScoreIds[] = $existingKpiScore->id;
                    }
                } else {
                    // Create new
                    $newKpiScore = $appraisal->kpiScores()->create([
                        'kpi_id' => $kpiScore['kpi_id'],
                        'self_score' => $kpiScore['self_score'] ?? null,
                        'manager_score' => $kpiScore['manager_score'] ?? null,
                        'final_score' => $kpiScore['final_score'] ?? null,
                        'comments' => $kpiScore['comments'] ?? null,
                    ]);
                    $existingKpiScoreIds[] = $newKpiScore->id;
                }
            }

            // Delete KPI scores that were removed
            $appraisal->kpiScores()->whereNotIn('id', $existingKpiScoreIds)->delete();

            // Recalculate final score from KPI scores if KPIs exist
            if ($appraisal->kpiScores()->exists()) {
                $finalScore = $appraisal->calculateFinalScore();
                $rating = $appraisal->determineRating();
                $appraisal->update([
                    'final_score' => $finalScore,
                    'rating' => $rating,
                ]);
            }

            // Handle approval
            if ($validated['status'] === 'approved' && $appraisal->status !== 'approved') {
                $appraisal->update([
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            } elseif ($validated['status'] !== 'approved' && $appraisal->approved_by) {
                $appraisal->update([
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appraisal updated successfully.'
                ]);
            }

            return redirect()->route('hr.appraisals.index')
                ->with('success', 'Appraisal updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update appraisal: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appraisal $appraisal)
    {
        if ($appraisal->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($appraisal->status === Appraisal::STATUS_LOCKED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete locked appraisal.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $appraisal->kpiScores()->delete();
            $appraisal->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Appraisal deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete appraisal: ' . $e->getMessage()
            ], 500);
        }
    }
}
