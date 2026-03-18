<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExitClearanceItem extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_exit_clearance_items';

    protected $fillable = [
        'exit_id',
        'clearance_item',
        'department',
        'status',
        'completed_by',
        'completed_at',
        'notes',
        'sequence_order',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_WAIVED = 'waived';

    public function exit()
    {
        return $this->belongsTo(EmployeeExit::class, 'exit_id');
    }

    public function completedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'completed_by');
    }

    public function markCompleted($userId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_by' => $userId ?? auth()->id(),
            'completed_at' => now(),
        ]);

        // Update exit clearance status if all items are completed
        $exit = $this->exit;
        $pendingItems = $exit->clearanceItems()->where('status', self::STATUS_PENDING)->count();
        
        if ($pendingItems === 0) {
            $exit->update(['clearance_status' => 'completed']);
        } elseif ($exit->clearance_status === 'pending') {
            $exit->update(['clearance_status' => 'in_progress']);
        }
    }
}

