<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'date',
        'name',
        'description',
        'half_day',
        'recurring',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'half_day' => 'boolean',
        'recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    /**
     * Check if a date is a public holiday for a company/branch
     */
    public static function isHoliday($date, $companyId, $branchId = null)
    {
        $query = static::where('company_id', $companyId)
            ->where('date', $date)
            ->where('is_active', true);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            });
        } else {
            $query->whereNull('branch_id');
        }

        return $query->exists();
    }

    /**
     * Get holidays between two dates
     */
    public static function getHolidaysBetween($startDate, $endDate, $companyId, $branchId = null)
    {
        $query = static::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_active', true);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            });
        } else {
            $query->whereNull('branch_id');
        }

        return $query->get();
    }
}

