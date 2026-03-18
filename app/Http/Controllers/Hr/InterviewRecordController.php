<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\InterviewRecord;
use App\Models\Hr\Applicant;
use App\Models\Hr\VacancyRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class InterviewRecordController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $interviews = InterviewRecord::whereHas('applicant', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['applicant', 'vacancyRequisition', 'interviewer'])
            ->orderBy('interview_date', 'desc')
            ->orderBy('interview_time', 'desc');

            return DataTables::of($interviews)
                ->addIndexColumn()
                ->addColumn('applicant_name', function ($interview) {
                    return $interview->applicant->full_name;
                })
                ->addColumn('vacancy_title', function ($interview) {
                    return $interview->vacancyRequisition->job_title ?? 'N/A';
                })
                ->addColumn('interview_datetime', function ($interview) {
                    return $interview->interview_date->format('d M Y') . ' ' . date('H:i', strtotime($interview->interview_time));
                })
                ->addColumn('interview_type_badge', function ($interview) {
                    $badges = [
                        'phone' => 'info',
                        'video' => 'primary',
                        'in_person' => 'success',
                        'panel' => 'warning',
                    ];
                    $badge = $badges[$interview->interview_type] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $interview->interview_type)) . '</span>';
                })
                ->addColumn('recommendation_badge', function ($interview) {
                    if (!$interview->recommendation) {
                        return '-';
                    }
                    $badges = [
                        'hire' => 'success',
                        'maybe' => 'warning',
                        'reject' => 'danger',
                        'next_round' => 'info',
                    ];
                    $badge = $badges[$interview->recommendation] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $interview->recommendation)) . '</span>';
                })
                ->addColumn('action', function ($interview) {
                    $viewBtn = '<a href="' . route('hr.interview-records.show', $interview->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.interview-records.edit', $interview->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['interview_type_badge', 'recommendation_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.lifecycle.interview-records.index');
    }

    public function create(Request $request)
    {
        $companyId = current_company_id();
        $applicants = Applicant::where('company_id', $companyId)
            ->orderBy('first_name')
            ->get();
        
        $vacancies = VacancyRequisition::where('company_id', $companyId)
            ->where('status', VacancyRequisition::STATUS_APPROVED)
            ->orderBy('job_title')
            ->get();

        $applicantId = $request->get('applicant_id');
        $vacancyId = $request->get('vacancy_id');

        return view('hr-payroll.lifecycle.interview-records.create', compact('applicants', 'vacancies', 'applicantId', 'vacancyId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'applicant_id' => 'required|exists:hr_applicants,id',
            'vacancy_requisition_id' => 'nullable|exists:hr_vacancy_requisitions,id',
            'interview_type' => 'required|in:phone,video,in_person,panel',
            'round_number' => 'nullable|string|max:10',
            'interview_date' => 'required|date',
            'interview_time' => 'required',
            'location' => 'nullable|string|max:200',
            'meeting_link' => 'nullable|url',
            'interviewers' => 'nullable|array',
            'interviewers.*' => 'exists:users,id',
            'overall_score' => 'nullable|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
            'strengths' => 'nullable|string',
            'weaknesses' => 'nullable|string',
            'recommendation' => 'nullable|in:hire,maybe,reject,next_round',
        ]);

        DB::beginTransaction();
        try {
            InterviewRecord::create(array_merge($validated, [
                'interviewed_by' => auth()->id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Interview record created successfully.'
                ]);
            }

            return redirect()->route('hr.interview-records.index')
                ->with('success', 'Interview record created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create interview record: ' . $e->getMessage()]);
        }
    }

    public function show(InterviewRecord $interviewRecord)
    {
        if ($interviewRecord->applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $interviewRecord->load(['applicant', 'vacancyRequisition', 'interviewer']);
        return view('hr-payroll.lifecycle.interview-records.show', compact('interviewRecord'));
    }

    public function edit(InterviewRecord $interviewRecord)
    {
        if ($interviewRecord->applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $companyId = current_company_id();
        $applicants = Applicant::where('company_id', $companyId)
            ->orderBy('first_name')
            ->get();
        
        $vacancies = VacancyRequisition::where('company_id', $companyId)
            ->where('status', VacancyRequisition::STATUS_APPROVED)
            ->orderBy('job_title')
            ->get();

        return view('hr-payroll.lifecycle.interview-records.edit', compact('interviewRecord', 'applicants', 'vacancies'));
    }

    public function update(Request $request, InterviewRecord $interviewRecord)
    {
        if ($interviewRecord->applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'applicant_id' => 'required|exists:hr_applicants,id',
            'vacancy_requisition_id' => 'nullable|exists:hr_vacancy_requisitions,id',
            'interview_type' => 'required|in:phone,video,in_person,panel',
            'round_number' => 'nullable|string|max:10',
            'interview_date' => 'required|date',
            'interview_time' => 'required',
            'location' => 'nullable|string|max:200',
            'meeting_link' => 'nullable|url',
            'interviewers' => 'nullable|array',
            'interviewers.*' => 'exists:users,id',
            'overall_score' => 'nullable|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
            'strengths' => 'nullable|string',
            'weaknesses' => 'nullable|string',
            'recommendation' => 'nullable|in:hire,maybe,reject,next_round',
        ]);

        DB::beginTransaction();
        try {
            $interviewRecord->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Interview record updated successfully.'
                ]);
            }

            return redirect()->route('hr.interview-records.index')
                ->with('success', 'Interview record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update interview record: ' . $e->getMessage()]);
        }
    }

    public function destroy(InterviewRecord $interviewRecord)
    {
        if ($interviewRecord->applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $interviewRecord->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Interview record deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete interview record: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'vacancy_requisition_id' => 'required|exists:hr_vacancy_requisitions,id',
            'interview_type' => 'required|in:phone,video,in_person,panel',
            'interview_stage' => 'required|string',
            'interview_method' => 'required|string',
            'interview_date' => 'required|date',
            'interview_time' => 'required',
            'location' => 'nullable|string',
            'meeting_link' => 'nullable|url',
            'interviewers' => 'required|array',
            'interviewers.*' => 'exists:users,id',
        ]);

        $vacancy = VacancyRequisition::findOrFail($validated['vacancy_requisition_id']);
        
        // Get only eligible applicants
        $applicants = $vacancy->applicants()
            ->where('status', \App\Models\Hr\Applicant::STATUS_ELIGIBLE)
            ->get();

        if ($applicants->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No eligible applicants found for this vacancy.'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($applicants as $applicant) {
                InterviewRecord::create([
                    'applicant_id' => $applicant->id,
                    'vacancy_requisition_id' => $vacancy->id,
                    'interview_type' => $validated['interview_type'],
                    'round_number' => $validated['interview_stage'],
                    'interview_date' => $validated['interview_date'],
                    'interview_time' => $validated['interview_time'],
                    'location' => $validated['location'],
                    'meeting_link' => $validated['meeting_link'],
                    'interviewers' => $validated['interviewers'],
                    'status' => InterviewRecord::STATUS_INVITED,
                    'interviewed_by' => auth()->id(),
                ]);

                // Update applicant status
                $applicant->update(['status' => \App\Models\Hr\Applicant::STATUS_INVITED]);
            }

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => count($applicants) . ' interview invitations have been successfully dispatched and records created.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to process bulk invitations: ' . $e->getMessage()], 500);
        }
    }
}
