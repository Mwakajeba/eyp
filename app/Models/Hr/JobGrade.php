<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobGrade extends Model
{
    use LogsActivity;

    protected $table = 'hr_job_grades';

    protected $fillable = [
        'company_id',
        'grade_code',
        'grade_name',
        'minimum_salary',
        'midpoint_salary',
        'maximum_salary',
        'is_active',
    ];

    protected $casts = [
        'minimum_salary' => 'decimal:2',
        'midpoint_salary' => 'decimal:2',
        'maximum_salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'grade_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if salary falls within grade band
     */
    public function isSalaryInRange(float $salary): bool
    {
        if ($this->minimum_salary && $salary < $this->minimum_salary) {
            return false;
        }
        if ($this->maximum_salary && $salary > $this->maximum_salary) {
            return false;
        }
        return true;
    }

    /**
     * Get salary range as string
     */
    public function getSalaryRangeAttribute(): string
    {
        $min = $this->minimum_salary ? number_format($this->minimum_salary, 2) : 'N/A';
        $max = $this->maximum_salary ? number_format($this->maximum_salary, 2) : 'N/A';
        return "{$min} - {$max}";
    }
}
