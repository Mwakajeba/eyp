@extends('layouts.main')

@section('title', 'Edit Appraisal')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Performance', 'url' => '#', 'icon' => 'bx bx-trophy'],
            ['label' => 'Appraisals', 'url' => route('hr.appraisals.index'), 'icon' => 'bx bx-clipboard'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Appraisal</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.appraisals.update', $appraisal->id) }}" id="appraisalForm">
                    @csrf
                    @method('PUT')
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
                                <option value="">-- Select Employee --</option>
                                @foreach($employees ?? [] as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id', $appraisal->employee_id) == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} ({{ $employee->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Appraisal Cycle <span class="text-danger">*</span></label>
                            <select name="cycle_id" id="cycle_id" class="form-select @error('cycle_id') is-invalid @enderror" required>
                                <option value="">-- Select Cycle --</option>
                                @foreach($cycles ?? [] as $cycle)
                                    <option value="{{ $cycle->id }}" {{ old('cycle_id', $appraisal->cycle_id) == $cycle->id ? 'selected' : '' }}>
                                        {{ $cycle->cycle_name }} ({{ $cycle->start_date->format('M Y') }} - {{ $cycle->end_date->format('M Y') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('cycle_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Appraiser <span class="text-danger">*</span></label>
                            <select name="appraiser_id" id="appraiser_id" class="form-select select2-single @error('appraiser_id') is-invalid @enderror" required>
                                <option value="">-- Select Appraiser --</option>
                                @foreach(\App\Models\User::where('company_id', current_company_id())->get() as $user)
                                    <option value="{{ $user->id }}" {{ old('appraiser_id', $appraisal->appraiser_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('appraiser_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4" />

                    <!-- KPI Scores Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-primary">
                                <i class="bx bx-target-lock me-1"></i>KPI Scores
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addKpiBtn">
                                <i class="bx bx-plus me-1"></i>Add KPI
                            </button>
                        </div>
                        <div id="kpi-scores-container">
                            @if(old('kpi_scores'))
                                @foreach(old('kpi_scores') as $index => $kpiScore)
                                    @include('hr-payroll.performance.appraisals._kpi_score_row', ['index' => $index, 'kpiScore' => $kpiScore, 'kpis' => $kpis ?? []])
                                @endforeach
                            @elseif($appraisal->kpiScores->count() > 0)
                                @foreach($appraisal->kpiScores as $index => $kpiScore)
                                    @include('hr-payroll.performance.appraisals._kpi_score_row', [
                                        'index' => $index, 
                                        'kpiScore' => ['id' => $kpiScore->id, 'kpi_id' => $kpiScore->kpi_id, 'self_score' => $kpiScore->self_score, 'manager_score' => $kpiScore->manager_score, 'final_score' => $kpiScore->final_score, 'comments' => $kpiScore->comments],
                                        'kpis' => $kpis ?? []
                                    ])
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <hr class="my-4" />

                    <!-- Scores Summary -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Self Assessment Score</label>
                            <input type="number" name="self_assessment_score" step="0.01" min="0" max="100" 
                                   class="form-control @error('self_assessment_score') is-invalid @enderror" 
                                   value="{{ old('self_assessment_score', $appraisal->self_assessment_score) }}" />
                            @error('self_assessment_score')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Manager Score</label>
                            <input type="number" name="manager_score" step="0.01" min="0" max="100" 
                                   class="form-control @error('manager_score') is-invalid @enderror" 
                                   value="{{ old('manager_score', $appraisal->manager_score) }}" />
                            @error('manager_score')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Final Score</label>
                            <input type="number" name="final_score" step="0.01" min="0" max="100" 
                                   class="form-control @error('final_score') is-invalid @enderror" 
                                   value="{{ old('final_score', $appraisal->final_score) }}" readonly />
                            @error('final_score')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Rating</label>
                            <select name="rating" class="form-select @error('rating') is-invalid @enderror">
                                <option value="">Auto-determined</option>
                                <option value="excellent" {{ old('rating', $appraisal->rating) == 'excellent' ? 'selected' : '' }}>Excellent</option>
                                <option value="good" {{ old('rating', $appraisal->rating) == 'good' ? 'selected' : '' }}>Good</option>
                                <option value="average" {{ old('rating', $appraisal->rating) == 'average' ? 'selected' : '' }}>Average</option>
                                <option value="needs_improvement" {{ old('rating', $appraisal->rating) == 'needs_improvement' ? 'selected' : '' }}>Needs Improvement</option>
                            </select>
                            @error('rating')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="draft" {{ old('status', $appraisal->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="submitted" {{ old('status', $appraisal->status) == 'submitted' ? 'selected' : '' }}>Submitted</option>
                                <option value="approved" {{ old('status', $appraisal->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="locked" {{ old('status', $appraisal->status) == 'locked' ? 'selected' : '' }}>Locked</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Appraisal
                        </button>
                        <a href="{{ route('hr.appraisals.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
let kpiIndex = {{ old('kpi_scores') ? count(old('kpi_scores')) : ($appraisal->kpiScores->count() ?? 0) }};
const availableKpis = @json($kpis ?? []);

$(document).ready(function() {
    $('#addKpiBtn').click(function() {
        addKpiRow();
    });

    $(document).on('click', '.remove-kpi-btn', function() {
        $(this).closest('.kpi-score-row').remove();
        updateFinalScore();
    });

    $(document).on('input', '.manager-score, .self-score', function() {
        let row = $(this).closest('.kpi-score-row');
        let managerScore = parseFloat(row.find('.manager-score').val()) || 0;
        let selfScore = parseFloat(row.find('.self-score').val()) || 0;
        row.find('.final-score').val(managerScore || selfScore);
        updateFinalScore();
    });

    $('#appraisalForm').on('submit', function(e) {
        updateFinalScore();
    });
});

function addKpiRow() {
    let kpiHtml = `
        <div class="kpi-score-row border rounded p-3 mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">KPI <span class="text-danger">*</span></label>
                    <select name="kpi_scores[${kpiIndex}][kpi_id]" class="form-select kpi-select" required>
                        <option value="">-- Select KPI --</option>
                        ${availableKpis.map(kpi => `<option value="${kpi.id}">${kpi.kpi_code} - ${kpi.kpi_name}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Self Score</label>
                    <input type="number" name="kpi_scores[${kpiIndex}][self_score]" step="0.01" min="0" max="100" 
                           class="form-control self-score" placeholder="0.00" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Manager Score</label>
                    <input type="number" name="kpi_scores[${kpiIndex}][manager_score]" step="0.01" min="0" max="100" 
                           class="form-control manager-score" placeholder="0.00" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Final Score</label>
                    <input type="number" name="kpi_scores[${kpiIndex}][final_score]" step="0.01" min="0" max="100" 
                           class="form-control final-score" placeholder="Auto" readonly />
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-kpi-btn">
                        <i class="bx bx-trash"></i> Remove
                    </button>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Comments</label>
                    <textarea name="kpi_scores[${kpiIndex}][comments]" class="form-control" rows="2" placeholder="Comments..."></textarea>
                </div>
            </div>
        </div>
    `;
    $('#kpi-scores-container').append(kpiHtml);
    kpiIndex++;
}

function updateFinalScore() {
    let managerScore = parseFloat($('input[name="manager_score"]').val()) || 0;
    let selfScore = parseFloat($('input[name="self_assessment_score"]').val()) || 0;
    let finalScore = managerScore || selfScore;
    
    if (finalScore > 0) {
        $('input[name="final_score"]').val(finalScore.toFixed(2));
        
        let rating = '';
        if (finalScore >= 90) rating = 'excellent';
        else if (finalScore >= 75) rating = 'good';
        else if (finalScore >= 60) rating = 'average';
        else rating = 'needs_improvement';
        
        if ($('select[name="rating"]').val() === '') {
            $('select[name="rating"]').val(rating);
        }
    }
}
</script>
@endpush

