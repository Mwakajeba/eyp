<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppraisalKpiScore extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_appraisal_kpi_scores';

    protected $fillable = [
        'appraisal_id',
        'kpi_id',
        'self_score',
        'manager_score',
        'final_score',
        'comments',
    ];

    protected $casts = [
        'self_score' => 'decimal:2',
        'manager_score' => 'decimal:2',
        'final_score' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'appraisal_id');
    }

    public function kpi()
    {
        return $this->belongsTo(Kpi::class, 'kpi_id');
    }

    /**
     * Calculate final score (defaults to manager score if available, else self score)
     */
    public function calculateFinalScore(): float
    {
        if ($this->manager_score !== null) {
            return $this->manager_score;
        }
        return $this->self_score ?? 0;
    }
}

