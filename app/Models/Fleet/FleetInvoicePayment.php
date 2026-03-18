<?php

namespace App\Models\Fleet;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetInvoicePayment extends Model
{
    use HasFactory;

    protected $table = 'fleet_invoice_payments';

    protected $fillable = [
        'fleet_invoice_id',
        'company_id',
        'branch_id',
        'amount',
        'payment_date',
        'bank_account_id',
        'reference_number',
        'notes',
        'attachments',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'attachments' => 'array',
    ];

    public function fleetInvoice()
    {
        return $this->belongsTo(FleetInvoice::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
