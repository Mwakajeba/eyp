<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class Position extends Model
{
    use LogsActivity;
    
    protected $table = 'hr_positions';

    protected $fillable = [
        'company_id',
        'department_id',
        'title',
        'description',
        // Phase 1: Position Control fields
        'position_code',
        'position_title',
        'job_description',
        'grade_id',
        'approved_headcount',
        'filled_headcount',
        'budgeted_salary',
        'status',
        'effective_date',
        'end_date',
    ];

    protected $casts = [
        'approved_headcount' => 'integer',
        'filled_headcount' => 'integer',
        'budgeted_salary' => 'decimal:2',
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Hr\Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'position_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class, 'grade_id');
    }

    public function positionAssignments(): HasMany
    {
        return $this->hasMany(PositionAssignment::class);
    }

    /**
     * Get available headcount
     */
    public function getAvailableHeadcountAttribute(): int
    {
        return max(0, $this->approved_headcount - $this->filled_headcount);
    }

    /**
     * Check if position has available headcount
     */
    public function hasAvailableHeadcount(): bool
    {
        return $this->available_headcount > 0;
    }

    /**
     * Check if position is active
     */
    public function isActive(): bool
    {
        return $this->status === 'approved' &&
               ($this->effective_date === null || $this->effective_date <= now()) &&
               ($this->end_date === null || $this->end_date >= now());
    }

    /**
     * Get the hash ID for the position
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Resolve the model from the route parameter
     *
     * @param string $value
     * @param string|null $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded) && isset($decoded[0])) {
            $position = static::where('id', $decoded[0])->first();
            if ($position) {
                return $position;
            }
        }
        
        // Fallback to regular ID lookup (in case it's a numeric ID)
        if (is_numeric($value)) {
            return static::where('id', $value)->first();
        }
        
        // If neither hash ID nor numeric ID works, return null (will trigger 404)
        return null;
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKey()
    {
        return $this->hash_id;
    }
}
