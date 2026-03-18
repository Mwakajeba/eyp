<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\OfferLetter;
use App\Models\Hr\Applicant;
use App\Models\Hr\VacancyRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class OfferLetterController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $offers = OfferLetter::whereHas('applicant', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['applicant', 'vacancyRequisition', 'preparedByUser', 'approvedByUser'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($offers)
                ->addIndexColumn()
                ->addColumn('applicant_name', function ($offer) {
                    return $offer->applicant->full_name;
                })
                ->addColumn('vacancy_title', function ($offer) {
                    return $offer->vacancyRequisition->job_title ?? 'N/A';
                })
                ->addColumn('offered_salary_display', function ($offer) {
                    return number_format($offer->offered_salary, 2);
                })
                ->addColumn('expiry_status', function ($offer) {
                    if ($offer->isExpired()) {
                        return '<span class="badge bg-danger">Expired</span>';
                    }
                    if ($offer->expiry_date->isFuture()) {
                        $daysLeft = now()->diffInDays($offer->expiry_date);
                        $badge = $daysLeft <= 7 ? 'warning' : 'success';
                        return '<span class="badge bg-' . $badge . '">' . $daysLeft . ' days left</span>';
                    }
                    return '-';
                })
                ->addColumn('status_badge', function ($offer) {
                    $badges = [
                        'draft' => 'secondary',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'dark',
                        'withdrawn' => 'dark',
                    ];
                    $badge = $badges[$offer->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $offer->status)) . '</span>';
                })
                ->addColumn('action', function ($offer) {
                    $viewBtn = '<a href="' . route('hr.offer-letters.show', $offer->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.offer-letters.edit', $offer->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['expiry_status', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.lifecycle.offer-letters.index');
    }

    public function create(Request $request)
    {
        $companyId = current_company_id();
        $applicants = Applicant::where('company_id', $companyId)
            ->whereIn('status', [Applicant::STATUS_INTERVIEW, Applicant::STATUS_OFFERED])
            ->orderBy('first_name')
            ->get();
        
        $vacancies = VacancyRequisition::where('company_id', $companyId)
            ->where('status', VacancyRequisition::STATUS_APPROVED)
            ->orderBy('job_title')
            ->get();

        $applicantId = $request->get('applicant_id');
        $vacancyId = $request->get('vacancy_id');

        return view('hr-payroll.lifecycle.offer-letters.create', compact('applicants', 'vacancies', 'applicantId', 'vacancyId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'applicant_id' => 'required|exists:hr_applicants,id',
            'vacancy_requisition_id' => 'nullable|exists:hr_vacancy_requisitions,id',
            'offered_salary' => 'required|numeric|min:0',
            'offer_date' => 'required|date',
            'expiry_date' => 'required|date|after:offer_date',
            'proposed_start_date' => 'nullable|date',
            'terms_and_conditions' => 'nullable|string',
            'status' => 'required|in:draft,pending_approval,approved,sent,accepted,rejected,expired,withdrawn',
        ]);

        DB::beginTransaction();
        try {
            // Generate offer number
            $count = OfferLetter::whereHas('applicant', function($q) {
                $q->where('company_id', current_company_id());
            })->count() + 1;
            $offerNumber = 'OFR-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            $offer = OfferLetter::create(array_merge($validated, [
                'offer_number' => $offerNumber,
                'prepared_by' => auth()->id(),
            ]));

            // Update applicant status if offer is sent
            if ($validated['status'] === 'sent') {
                $offer->applicant->update(['status' => Applicant::STATUS_OFFERED]);
                $offer->update(['sent_at' => now()]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Offer letter created successfully.'
                ]);
            }

            return redirect()->route('hr.offer-letters.index')
                ->with('success', 'Offer letter created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create offer letter: ' . $e->getMessage()]);
        }
    }

    public function show(OfferLetter $offerLetter)
    {
        if ($offerLetter->applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $offerLetter->load(['applicant', 'vacancyRequisition', 'preparedByUser', 'approvedByUser']);
        return view('hr-payroll.lifecycle.offer-letters.show', compact('offerLetter'));
    }

    public function edit(OfferLetter $offerLetter)
    {
        if ($offerLetter->applicant->company_id !== current_company_id()) {
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

        return view('hr-payroll.lifecycle.offer-letters.edit', compact('offerLetter', 'applicants', 'vacancies'));
    }

    public function update(Request $request, OfferLetter $offerLetter)
    {
        if ($offerLetter->applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'applicant_id' => 'required|exists:hr_applicants,id',
            'vacancy_requisition_id' => 'nullable|exists:hr_vacancy_requisitions,id',
            'offered_salary' => 'required|numeric|min:0',
            'offer_date' => 'required|date',
            'expiry_date' => 'required|date|after:offer_date',
            'proposed_start_date' => 'nullable|date',
            'terms_and_conditions' => 'nullable|string',
            'status' => 'required|in:draft,pending_approval,approved,sent,accepted,rejected,expired,withdrawn',
            'response_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Handle approval
            if ($validated['status'] === 'approved' && $offerLetter->status !== 'approved') {
                $validated['approved_by'] = auth()->id();
                $validated['approved_at'] = now();
            }

            // Handle sending
            if ($validated['status'] === 'sent' && $offerLetter->status !== 'sent') {
                $validated['sent_at'] = now();
                $offerLetter->applicant->update(['status' => Applicant::STATUS_OFFERED]);
            }

            // Handle response
            if (in_array($validated['status'], ['accepted', 'rejected']) && !$offerLetter->responded_at) {
                $validated['responded_at'] = now();
                if ($validated['status'] === 'accepted') {
                    $offerLetter->applicant->update(['status' => Applicant::STATUS_HIRED]);
                }
            }

            $offerLetter->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Offer letter updated successfully.'
                ]);
            }

            return redirect()->route('hr.offer-letters.index')
                ->with('success', 'Offer letter updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update offer letter: ' . $e->getMessage()]);
        }
    }

    public function destroy(OfferLetter $offerLetter)
    {
        if ($offerLetter->applicant->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $offerLetter->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offer letter deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete offer letter: ' . $e->getMessage()
            ], 500);
        }
    }
}
