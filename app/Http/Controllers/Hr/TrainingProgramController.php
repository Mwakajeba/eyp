<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class TrainingProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $programs = TrainingProgram::where('company_id', current_company_id())
                ->orderBy('program_code');

            return DataTables::of($programs)
                ->addIndexColumn()
                ->addColumn('cost_display', function ($program) {
                    return $program->cost ? number_format($program->cost, 2) : '-';
                })
                ->addColumn('duration_display', function ($program) {
                    return $program->duration_days ? $program->duration_days . ' days' : '-';
                })
                ->addColumn('provider_badge', function ($program) {
                    $badge = $program->provider === 'internal' ? 'success' : 'info';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($program->provider ?? 'N/A') . '</span>';
                })
                ->addColumn('funding_source_badge', function ($program) {
                    if (!$program->funding_source) {
                        return '-';
                    }
                    $badges = [
                        'sdl' => 'primary',
                        'internal' => 'success',
                        'donor' => 'warning',
                    ];
                    $badge = $badges[$program->funding_source] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . strtoupper($program->funding_source) . '</span>';
                })
                ->addColumn('attendance_count', function ($program) {
                    return $program->attendance()->count();
                })
                ->addColumn('status_badge', function ($program) {
                    $badge = $program->is_active ? 'success' : 'secondary';
                    $text = $program->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('action', function ($program) {
                    $viewBtn = '<a href="' . route('hr.training-programs.show', $program->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.training-programs.edit', $program->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $program->id . '" data-name="' . $program->program_name . '"><i class="bx bx-trash"></i></button>';
                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['provider_badge', 'funding_source_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.training.programs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.training.programs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hr_training_programs')->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'program_name' => 'required|string|max:200',
            'provider' => 'nullable|in:internal,external',
            'cost' => 'nullable|numeric|min:0',
            'duration_days' => 'nullable|integer|min:1',
            'funding_source' => 'nullable|in:sdl,internal,donor',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            TrainingProgram::create(array_merge($validated, [
                'company_id' => current_company_id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Training program created successfully.'
                ]);
            }

            return redirect()->route('hr.training-programs.index')
                ->with('success', 'Training program created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create training program: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TrainingProgram $trainingProgram)
    {
        if ($trainingProgram->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $trainingProgram->load(['attendance.employee', 'trainingBonds.employee']);
        return view('hr-payroll.training.programs.show', compact('trainingProgram'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TrainingProgram $trainingProgram)
    {
        if ($trainingProgram->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('hr-payroll.training.programs.edit', compact('trainingProgram'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrainingProgram $trainingProgram)
    {
        if ($trainingProgram->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'program_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hr_training_programs')->ignore($trainingProgram->id)->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'program_name' => 'required|string|max:200',
            'provider' => 'nullable|in:internal,external',
            'cost' => 'nullable|numeric|min:0',
            'duration_days' => 'nullable|integer|min:1',
            'funding_source' => 'nullable|in:sdl,internal,donor',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $trainingProgram->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Training program updated successfully.'
                ]);
            }

            return redirect()->route('hr.training-programs.index')
                ->with('success', 'Training program updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update training program: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TrainingProgram $trainingProgram)
    {
        if ($trainingProgram->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            // Check if program has attendance or bonds
            if ($trainingProgram->attendance()->exists() || $trainingProgram->trainingBonds()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete training program. It has attendance records or training bonds.'
                ], 422);
            }

            $trainingProgram->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Training program deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete training program: ' . $e->getMessage()
            ], 500);
        }
    }
}
