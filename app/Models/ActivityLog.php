<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'model',
        'action',
        'description',
        'ip_address',
        'device',
        'activity_time',
        'model_id',
        'company_id',
        'branch_id',
    ];

    protected $casts = [
        'activity_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related revaluation history if this is a GlRevaluationHistory log
     */
    public function revaluationHistory()
    {
        return $this->belongsTo(GlRevaluationHistory::class, 'model_id');
    }

    /**
     * Get the related FX rate if this is an FxRate log
     */
    public function fxRate()
    {
        return $this->belongsTo(FxRate::class, 'model_id');
    }

    /**
     * Get action badge HTML
     */
    public function getActionBadgeAttribute()
    {
        $badgeClass = match($this->action) {
            'create' => 'success',
            'update' => 'primary',
            'delete' => 'danger',
            'post' => 'info',
            'reverse' => 'warning',
            'approve' => 'success',
            'reject' => 'danger',
            'check' => 'info',
            'lock' => 'danger',
            'unlock' => 'success',
            'activate' => 'success',
            'deactivate' => 'secondary',
            default => 'secondary'
        };
        return '<span class="badge bg-'.$badgeClass.'">'.ucfirst($this->action).'</span>';
    }

    /**
     * Get model badge HTML
     */
    public function getModelBadgeAttribute()
    {
        $badgeClass = match($this->model) {
            'GlRevaluationHistory' => 'info',
            'FxRate' => 'primary',
            'Journal' => 'success',
            'SalesInvoice' => 'warning',
            'PurchaseInvoice' => 'danger',
            default => 'secondary'
        };
        return '<span class="badge bg-'.$badgeClass.'">'.$this->model.'</span>';
    }
}
