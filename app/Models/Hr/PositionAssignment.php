<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionAssignment extends Model
{
    use LogsActivity;

    protected $table = 'hr_position_assignments';

    protected $fillable = [
        'employee_id',
        'position_id',
        'effective_date',
        'end_date',
        'is_acting',
        'acting_allowance_percent',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_acting' => 'boolean',
        'acting_allowance_percent' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now());
        });
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            });
    }

    public function scopeActing($query)
    {
        return $query->where('is_acting', true);
    }

    /**
     * Check if assignment is currently active
     */
    public function isActive(): bool
    {
        return $this->effective_date <= now() && 
               ($this->end_date === null || $this->end_date >= now());
    }
}
