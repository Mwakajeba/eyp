<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Applicant;
use App\Models\Hr\VacancyRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $applicants = Applicant::where('company_id', current_company_id())
                ->with(['vacancyRequisition', 'employee'])
                ->orderBy('created_at', 'desc');

            // Filter by vacancy requisition if provided
            if ($request->has('vacancy_requisition_id') && $request->vacancy_requisition_id) {
                $vacancyId = $request->vacancy_requisition_id;
                
                // Try to resolve the vacancy requisition to get the numeric ID
                $vacancy = new \App\Models\Hr\VacancyRequisition();
                $resolvedVacancy = $vacancy->resolveRouteBinding($vacancyId);
                
                if ($resolvedVacancy) {
                    $applicants->where('vacancy_requisition_id', $resolvedVacancy->id);
                }
            }

            return DataTables::of($applicants)
                ->addIndexColumn()
                ->addColumn('applicant_name', function ($applicant) {
                    return $applicant->full_name;
                })
                ->addColumn('score', function ($applicant) {
                    $score = number_format($applicant->total_eligibility_score, 0);
                    $class = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
                    return '<span class="badge bg-light-' . $class . ' text-' . $class . ' fw-bold">' . $score . '%</span>';
                })
                ->addColumn('vacancy_title', function ($applicant) {
                    return $applicant->vacancyRequisition->job_title ?? 'N/A';
                })
                ->addColumn('status_badge', function ($applicant) {
                    $badges = [
                        'applied' => 'secondary',
                        'eligible' => 'success',
                        'invited' => 'warning',
                        'screening' => 'info',
                        'interview' => 'primary',
                        'offered' => 'warning',
                        'hired' => 'success',
                        'rejected' => 'danger',
                        'withdrawn' => 'dark',
                    ];
                    $badge = $badges[$applicant->status] ?? 'secondary';
                    $text = $applicant->status === 'eligible' ? 'Validated – Eligible' : ($applicant->status === 'invited' ? 'Interview Invited' : ucfirst($applicant->status));
                    $html = '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                    
                    if ($applicant->submission_source === 'portal') {
                        $html .= '<br><small class="text-info"><i class="bx bx-globe me-1"></i>Portal</small>';
                    } else {
                        $html .= '<br><small class="text-muted"><i class="bx bx-edit me-1"></i>Manual</small>';
                    }
                    
                    return $html;
                })
                ->addColumn('converted_badge', function ($applicant) {
                    if ($applicant->isConverted()) {
                        return '<span class="badge bg-success"><i class="bx bx-check"></i> Converted</span>';
                    }
                    return '<span class="badge bg-secondary">Not Converted</span>';
                })
                ->addColumn('action', function ($applicant) {
                    $viewBtn = '<a href="' . route('hr.applicants.show', $applicant->hash_id) . '" class="btn btn-sm btn-outline-info me-1" title="View Details"><i class="bx bx-show"></i></a>';
                    
                    $shortlistBtn = '';
                    if (!$applicant->is_shortlisted && $applicant->status !== 'rejected') {
                        $shortlistBtn = '<button class="btn btn-sm btn-outline-success shortlist-btn me-1" data-id="' . $applicant->hash_id . '" title="Shortlist Candidate"><i class="bx bx-check-double"></i></button>';
                    } elseif ($applicant->is_shortlisted) {
                        $shortlistBtn = '<span class="badge bg-light-success text-success me-1"><i class="bx bx-check"></i> Shortlisted</span>';
                    }

                    $editBtn = '<a href="' . route('hr.applicants.edit', $applicant->hash_id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $applicant->hash_id . '"><i class="bx bx-trash"></i></button>';
                    
                    return $viewBtn . $shortlistBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['status_badge', 'converted_badge', 'action', 'score'])
                ->make(true);
        }

        $filteredVacancy = null;
        if ($request->has('vacancy_requisition_id')) {
            $vacancy = new \App\Models\Hr\VacancyRequisition();
            $filteredVacancy = $vacancy->resolveRouteBinding($request->vacancy_requisition_id);
        }

        return view('hr-payroll.lifecycle.applicants.index', compact('filteredVacancy'));
    }

    public function create(Request $request)
    {
        $vacancies = VacancyRequisition::where('company_id', current_company_id())
            ->where('status', VacancyRequisition::STATUS_APPROVED)
            ->orderBy('job_title')
            ->get();

        $vacancyId = $request->get('vacancy_id');

        return view('hr-payroll.lifecycle.applicants.create', compact('vacancies', 'vacancyId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vacancy_requisition_id' => 'nullable|exists:hr_vacancy_requisitions,id',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string|max:200',
            'years_of_experience' => 'nullable|integer|min:0',
            'cover_letter' => 'nullable|string',
            'status' => 'required|in:applied,screening,interview,offered,hired,rejected,withdrawn',
        ]);

        DB::beginTransaction();
        try {
            // Generate application number
            $count = Applicant::where('company_id', current_company_id())->count() + 1;
            $applicationNumber = 'APP-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            // Handle file uploads
            if ($request->hasFile('resume')) {
                $validated['resume_path'] = $request->file('resume')->store('applicants/resumes', 'public');
            }
            if ($request->hasFile('cv')) {
                $validated['cv_path'] = $request->file('cv')->store('applicants/cvs', 'public');
            }

            Applicant::create(array_merge($validated, [
                'company_id' => current_company_id(),
                'application_number' => $applicationNumber,
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Applicant created successfully.'
                ]);
            }

            return redirect()->route('hr.applicants.index')
                ->with('success', 'Applicant created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create applicant: ' . $e->getMessage()]);
        }
    }

    public function show(Applicant $applicant)
    {
        if ($applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $applicant->load(['vacancyRequisition', 'interviews', 'offerLetters', 'employee', 'eligibilityChecks.eligibilityRule', 'normalizedProfile']);
        return view('hr-payroll.lifecycle.applicants.show', compact('applicant'));
    }

    public function edit(Applicant $applicant)
    {
        if ($applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $vacancies = VacancyRequisition::where('company_id', current_company_id())
            ->where('status', VacancyRequisition::STATUS_APPROVED)
            ->orderBy('job_title')
            ->get();

        return view('hr-payroll.lifecycle.applicants.edit', compact('applicant', 'vacancies'));
    }

    public function update(Request $request, Applicant $applicant)
    {
        if ($applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'vacancy_requisition_id' => 'nullable|exists:hr_vacancy_requisitions,id',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string|max:200',
            'years_of_experience' => 'nullable|integer|min:0',
            'cover_letter' => 'nullable|string',
            'status' => 'required|in:applied,screening,interview,offered,hired,rejected,withdrawn',
        ]);

        DB::beginTransaction();
        try {
            // If submitted via portal, only allow updating status
            if ($applicant->submission_source === 'portal') {
                $applicant->update([
                    'status' => $validated['status']
                ]);
            } else {
                if ($request->hasFile('resume')) {
                    $validated['resume_path'] = $request->file('resume')->store('applicants/resumes', 'public');
                }
                if ($request->hasFile('cv')) {
                    $validated['cv_path'] = $request->file('cv')->store('applicants/cvs', 'public');
                }

                $applicant->update($validated);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Applicant updated successfully.'
                ]);
            }

            return redirect()->route('hr.applicants.index')
                ->with('success', 'Applicant updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update applicant: ' . $e->getMessage()]);
        }
    }

    public function destroy(Applicant $applicant)
    {
        if ($applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            if ($applicant->isConverted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete applicant. Already converted to employee.'
                ], 422);
            }

            $applicant->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Applicant deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete applicant: ' . $e->getMessage()
            ], 500);
        }
    }

    public function convertToEmployee(Applicant $applicant)
    {
        if ($applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($applicant->isConverted()) {
            return back()->withErrors(['error' => 'Applicant already converted to employee.']);
        }

        $normalized = $applicant->normalizedProfile;
        $vacancy = $applicant->vacancyRequisition;

        // Redirect to employee create with pre-filled data from normalized profile
        return redirect()->route('hr.employees.create', [
            'applicant_id' => $applicant->id,
            'first_name' => $applicant->first_name,
            'middle_name' => $applicant->middle_name,
            'last_name' => $applicant->last_name,
            'email' => $applicant->email,
            'phone_number' => $applicant->phone_number,
            'date_of_birth' => $applicant->date_of_birth?->format('Y-m-d'),
            'gender' => $applicant->gender,
            'address' => $applicant->address,
            // Use normalized data for job linkage
            'position_id' => $vacancy->position_id ?? null,
            'department_id' => $vacancy->department_id ?? null,
            'salary' => $applicant->offerLetters()->where('status', 'accepted')->first()?->offered_salary ?? null,
            'education_level' => $normalized->education_level ?? null,
            'total_experience' => $normalized->years_of_experience ?? null,
        ]);
    }

    public function overrideNormalization(Request $request, Applicant $applicant)
    {
        if ($applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'education_level' => 'required|string',
            'years_of_experience' => 'required|numeric',
            'current_role' => 'nullable|string|max:255',
            'override_reason' => 'required|string|max:1000',
        ]);

        $applicant->normalizedProfile()->update([
            'education_level' => $validated['education_level'],
            'years_of_experience' => $validated['years_of_experience'],
            'current_role' => $validated['current_role'],
            'is_manually_overridden' => true,
            'overridden_by' => auth()->id(),
            'overridden_at' => now(),
            'override_reason' => $validated['override_reason'],
            'requires_hr_review' => false,
        ]);

        return redirect()->back()->with('success', 'Profile data has been manually standardized and audited.');
    }

    public function shortlist(Request $request, Applicant $applicant)
    {
        if ($applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $applicant->update([
            'is_shortlisted' => true,
            'shortlisted_at' => now(),
            'shortlisted_by' => auth()->id(),
            'status' => 'screening'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Applicant has been added to the shortlist for panel review.'
        ]);
    }
}
