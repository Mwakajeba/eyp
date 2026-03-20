<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSubActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'project_activity_id',
        'sub_activity_name',
        'chart_account_id',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProjectActivity::class, 'project_activity_id');
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'chart_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}