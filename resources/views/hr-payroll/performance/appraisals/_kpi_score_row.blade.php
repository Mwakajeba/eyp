<div class="kpi-score-row border rounded p-3 mb-3">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">KPI <span class="text-danger">*</span></label>
            <select name="kpi_scores[{{ $index }}][kpi_id]" class="form-select kpi-select @error("kpi_scores.{$index}.kpi_id") is-invalid @enderror" required>
                <option value="">-- Select KPI --</option>
                @foreach($kpis ?? [] as $kpi)
                    <option value="{{ $kpi->id }}" 
                            {{ (old("kpi_scores.{$index}.kpi_id", isset($kpiScore['kpi_id']) ? $kpiScore['kpi_id'] : (isset($kpi) ? $kpi->id : '')) == $kpi->id) ? 'selected' : '' }}>
                        {{ $kpi->kpi_code }} - {{ $kpi->kpi_name }}
                    </option>
                @endforeach
            </select>
            @error("kpi_scores.{$index}.kpi_id")
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Self Score</label>
            <input type="number" name="kpi_scores[{{ $index }}][self_score]" step="0.01" min="0" max="100" 
                   class="form-control self-score @error("kpi_scores.{$index}.self_score") is-invalid @enderror" 
                   value="{{ old("kpi_scores.{$index}.self_score", $kpiScore['self_score'] ?? '') }}" placeholder="0.00" />
            @error("kpi_scores.{$index}.self_score")
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Manager Score</label>
            <input type="number" name="kpi_scores[{{ $index }}][manager_score]" step="0.01" min="0" max="100" 
                   class="form-control manager-score @error("kpi_scores.{$index}.manager_score") is-invalid @enderror" 
                   value="{{ old("kpi_scores.{$index}.manager_score", $kpiScore['manager_score'] ?? '') }}" placeholder="0.00" />
            @error("kpi_scores.{$index}.manager_score")
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Final Score</label>
            <input type="number" name="kpi_scores[{{ $index }}][final_score]" step="0.01" min="0" max="100" 
                   class="form-control final-score @error("kpi_scores.{$index}.final_score") is-invalid @enderror" 
                   value="{{ old("kpi_scores.{$index}.final_score", $kpiScore['final_score'] ?? '') }}" placeholder="Auto" readonly />
            @error("kpi_scores.{$index}.final_score")
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-kpi-btn">
                <i class="bx bx-trash"></i> Remove
            </button>
        </div>
        <div class="col-md-12">
            <label class="form-label">Comments</label>
            <textarea name="kpi_scores[{{ $index }}][comments]" class="form-control @error("kpi_scores.{$index}.comments") is-invalid @enderror" 
                      rows="2" placeholder="Comments...">{{ old("kpi_scores.{$index}.comments", $kpiScore['comments'] ?? '') }}</textarea>
            @error("kpi_scores.{$index}.comments")
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    @if(isset($kpiScore['id']))
        <input type="hidden" name="kpi_scores[{{ $index }}][id]" value="{{ $kpiScore['id'] }}">
    @endif
</div>

