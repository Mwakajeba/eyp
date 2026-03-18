<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreRequisitionReturn extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'store_requisition_id',
        'return_date',
        'return_reason',
        'total_return_amount',
        'processed_by',
        'company_id',
        'branch_id'
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_return_amount' => 'decimal:2'
    ];

    public function storeRequisition(): BelongsTo
    {
        return $this->belongsTo(StoreRequisition::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(StoreRequisitionReturnItem::class);
    }
}
