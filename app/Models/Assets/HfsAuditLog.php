<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HfsAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'hfs_id',
        'action',
        'action_type',
        'user_id',
        'action_date',
        'old_values',
        'new_values',
        'description',
        'related_id',
        'related_type',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'action_date' => 'datetime',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relationships
    public function hfsRequest()
    {
        return $this->belongsTo(HfsRequest::class, 'hfs_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function related()
    {
        return $this->morphTo('related', 'related_type', 'related_id');
    }

    // Scopes
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('action_date', '>=', now()->subDays($days));
    }
}
