<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appraisal extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_appraisals';

    protected $fillable = [
        'employee_id',
        'cycle_id',
        'appraiser_id',
        'self_assessment_score',
        'manager_score',
        'final_score',
        'rating',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'self_assessment_score' => 'decimal:2',
        'manager_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Rating values
     */
    const RATING_EXCELLENT = 'excellent';
    const RATING_GOOD = 'good';
    const RATING_AVERAGE = 'average';
    const RATING_NEEDS_IMPROVEMENT = 'needs_improvement';

    /**
     * Status values
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_LOCKED = 'locked';

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function cycle()
    {
        return $this->belongsTo(AppraisalCycle::class, 'cycle_id');
    }

    public function appraiser()
    {
        return $this->belongsTo(\App\Models\User::class, 'appraiser_id');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function kpiScores()
    {
        return $this->hasMany(AppraisalKpiScore::class, 'appraisal_id');
    }

    /**
     * Calculate final score from KPI scores
     */
    public function calculateFinalScore(): float
    {
        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($this->kpiScores as $kpiScore) {
            $kpi = $kpiScore->kpi;
            $weight = $kpi->weight_percent ?? 0;
            $score = $kpiScore->final_score ?? $kpiScore->manager_score ?? 0;

            if ($weight > 0 && $score > 0) {
                $totalWeightedScore += ($score * $weight / 100);
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? round($totalWeightedScore / ($totalWeight / 100), 2) : 0;
    }

    /**
     * Determine rating based on final score
     */
    public function determineRating(): ?string
    {
        $score = $this->final_score;

        if ($score >= 90) {
            return self::RATING_EXCELLENT;
        } elseif ($score >= 75) {
            return self::RATING_GOOD;
        } elseif ($score >= 60) {
            return self::RATING_AVERAGE;
        } else {
            return self::RATING_NEEDS_IMPROVEMENT;
        }
    }
}

