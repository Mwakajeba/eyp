<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HfsDiscontinuedFlag extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'hfs_id',
        'is_discontinued',
        'discontinued_date',
        'criteria_checked',
        'component_name',
        'component_description',
        'effects_on_pnl',
        'is_manual_override',
        'override_reason',
        'override_approved_by',
        'override_approved_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_discontinued' => 'boolean',
        'discontinued_date' => 'date',
        'criteria_checked' => 'array',
        'effects_on_pnl' => 'array',
        'is_manual_override' => 'boolean',
        'override_approved_at' => 'datetime',
    ];

    // Relationships
    public function hfsRequest()
    {
        return $this->belongsTo(HfsRequest::class, 'hfs_id');
    }

    public function overrideApprover()
    {
        return $this->belongsTo(\App\Models\User::class, 'override_approved_by');
    }

    // Scopes
    public function scopeDiscontinued($query)
    {
        return $query->where('is_discontinued', true);
    }

    // Helper methods
    public function meetsCriteria(): bool
    {
        if (!$this->criteria_checked) {
            return false;
        }

        $criteria = $this->criteria_checked;
        return ($criteria['is_component'] ?? false) &&
               ($criteria['represents_separate_major_line'] ?? false) &&
               ($criteria['is_part_of_single_plan'] ?? false) &&
               ($criteria['is_disposed_or_classified_hfs'] ?? false);
    }
}
