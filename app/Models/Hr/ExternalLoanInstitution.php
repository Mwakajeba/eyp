<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class ExternalLoanInstitution extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_external_loan_institutions';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'contact_person',
        'email',
        'phone',
        'address',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function externalLoans()
    {
        return $this->hasMany(ExternalLoan::class, 'institution_name', 'name')
            ->where('company_id', $this->company_id);
    }

    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function getHashIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }
}

