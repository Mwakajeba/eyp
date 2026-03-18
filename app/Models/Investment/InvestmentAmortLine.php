<?php

namespace App\Models\Investment;

use App\Models\Journal;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentAmortLine extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'investment_amort_line';

    protected $fillable = [
        'investment_id',
        'period_start',
        'period_end',
        'days',
        'opening_carrying_amount',
        'interest_income',
        'cash_flow',
        'amortization',
        'closing_carrying_amount',
        'eir_rate',
        'posted',
        'posted_at',
        'journal_id',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_carrying_amount' => 'decimal:2',
        'interest_income' => 'decimal:2',
        'cash_flow' => 'decimal:2',
        'amortization' => 'decimal:2',
        'closing_carrying_amount' => 'decimal:2',
        'eir_rate' => 'decimal:12',
        'posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    // Relationships
    public function investment()
    {
        return $this->belongsTo(InvestmentMaster::class, 'investment_id');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    // Scopes
    public function scopePosted($query)
    {
        return $query->where('posted', true);
    }

    public function scopePending($query)
    {
        return $query->where('posted', false);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_end', [$startDate, $endDate]);
    }

    // Helper methods
    public function isPosted(): bool
    {
        return $this->posted;
    }

    public function isPending(): bool
    {
        return !$this->posted;
    }
}

