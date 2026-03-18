<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HfsValuation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'hfs_id',
        'valuation_date',
        'fair_value',
        'costs_to_sell',
        'fv_less_costs',
        'carrying_amount',
        'impairment_amount',
        'is_reversal',
        'original_carrying_before_impairment',
        'impairment_journal_id',
        'gl_posted',
        'gl_posted_at',
        'valuator_name',
        'valuator_license',
        'valuator_company',
        'report_ref',
        'valuation_report_path',
        'is_override',
        'override_reason',
        'override_approved_by',
        'override_approved_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'fair_value' => 'decimal:2',
        'costs_to_sell' => 'decimal:2',
        'fv_less_costs' => 'decimal:2',
        'carrying_amount' => 'decimal:2',
        'impairment_amount' => 'decimal:2',
        'is_reversal' => 'boolean',
        'original_carrying_before_impairment' => 'decimal:2',
        'gl_posted' => 'boolean',
        'gl_posted_at' => 'datetime',
        'is_override' => 'boolean',
        'override_approved_at' => 'datetime',
    ];

    // Relationships
    public function hfsRequest()
    {
        return $this->belongsTo(HfsRequest::class, 'hfs_id');
    }

    public function impairmentJournal()
    {
        return $this->belongsTo(\App\Models\Journal::class, 'impairment_journal_id');
    }

    public function overrideApprover()
    {
        return $this->belongsTo(\App\Models\User::class, 'override_approved_by');
    }

    // Scopes
    public function scopePosted($query)
    {
        return $query->where('gl_posted', true);
    }

    public function scopeReversals($query)
    {
        return $query->where('is_reversal', true);
    }

    // Helper methods
    public function calculateFvLessCosts(): float
    {
        return $this->fair_value - $this->costs_to_sell;
    }

    public function calculateImpairment(): float
    {
        $fvLessCosts = $this->calculateFvLessCosts();
        if ($fvLessCosts < $this->carrying_amount) {
            return $this->carrying_amount - $fvLessCosts;
        }
        return 0;
    }
}
