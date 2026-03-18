<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingBond extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_training_bonds';

    protected $fillable = [
        'employee_id',
        'training_program_id',
        'bond_amount',
        'bond_period_months',
        'start_date',
        'end_date',
        'recovery_rules',
        'status',
    ];

    protected $casts = [
        'bond_amount' => 'decimal:2',
        'bond_period_months' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'recovery_rules' => 'array',
    ];

    /**
     * Status values
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_FULFILLED = 'fulfilled';
    const STATUS_RECOVERED = 'recovered';

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    /**
     * Check if bond is fulfilled (employee stayed until end date)
     */
    public function isFulfilled(): bool
    {
        return $this->end_date <= now() && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if bond is expired (past end date and not fulfilled)
     */
    public function isExpired(): bool
    {
        return $this->end_date < now() && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Calculate remaining bond period in days
     */
    public function getRemainingDaysAttribute(): int
    {
        if ($this->end_date <= now()) {
            return 0;
        }
        return now()->diffInDays($this->end_date);
    }
}

