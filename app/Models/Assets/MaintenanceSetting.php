<?php

namespace App\Models\Assets;

use App\Models\Branch;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceSetting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'setting_key',
        'setting_name',
        'setting_value',
        'description',
        'setting_type',
        'updated_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper methods
    public function getValue()
    {
        switch ($this->setting_type) {
            case 'boolean':
                return (bool) $this->setting_value;
            case 'number':
                return (int) $this->setting_value;
            case 'decimal':
                return (float) $this->setting_value;
            case 'chart_account_id':
                return $this->setting_value ? ChartAccount::find($this->setting_value) : null;
            case 'json':
                return json_decode($this->setting_value, true);
            default:
                return $this->setting_value;
        }
    }

    // Static helper to get setting
    public static function getSetting($key, $companyId, $branchId = null, $default = null)
    {
        $setting = self::where('company_id', $companyId)
            ->where('setting_key', $key)
            ->where(function($query) use ($branchId) {
                if ($branchId) {
                    $query->where('branch_id', $branchId)->orWhereNull('branch_id');
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->orderByDesc('branch_id') // Branch-specific takes precedence
            ->first();

        if ($setting) {
            return $setting->getValue();
        }

        return $default;
    }
}
