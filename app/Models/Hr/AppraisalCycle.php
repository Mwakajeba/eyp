<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppraisalCycle extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_appraisal_cycles';

    protected $fillable = [
        'company_id',
        'cycle_name',
        'cycle_type',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Cycle types
     */
    const TYPE_ANNUAL = 'annual';
    const TYPE_SEMI_ANNUAL = 'semi_annual';
    const TYPE_QUARTERLY = 'quarterly';
    const TYPE_PROBATION = 'probation';

    /**
     * Status values
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Relationships
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function appraisals()
    {
        return $this->hasMany(Appraisal::class, 'cycle_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForDate($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }
}

