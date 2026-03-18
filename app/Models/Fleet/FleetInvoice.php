<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Hr\Department;
use App\Models\ChartAccount;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class FleetInvoice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'fleet_invoices';

    protected $fillable = [
        'company_id',
        'branch_id',
        'invoice_number',
        'trip_id',
        'vehicle_id',
        'driver_id',
        'route_id',
        'customer_id',
        'department_id',
        'invoice_date',
        'due_date',
        'invoice_type',
        'status',
        'billing_period_start',
        'billing_period_end',
        'revenue_model',
        'subtotal',
        'discount_amount',
        'discount_type',
        'tax_amount',
        'tax_rate',
        'total_amount',
        'paid_amount',
        'balance_due',
        'payment_terms',
        'payment_days',
        'currency',
        'exchange_rate',
        'number_of_trips',
        'total_distance_km',
        'total_hours',
        'gl_account_id',
        'is_posted_to_gl',
        'gl_journal_id',
        'gl_posted_at',
        'notes',
        'terms_conditions',
        'attachments',
        'sent_at',
        'paid_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'total_distance_km' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'is_posted_to_gl' => 'boolean',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'gl_posted_at' => 'datetime',
        'attachments' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function trip()
    {
        return $this->belongsTo(FleetTrip::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(\App\Models\Assets\Asset::class, 'vehicle_id');
    }

    public function driver()
    {
        return $this->belongsTo(FleetDriver::class);
    }

    public function route()
    {
        return $this->belongsTo(FleetRoute::class);
    }

    public function payments()
    {
        return $this->hasMany(FleetInvoicePayment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function items()
    {
        return $this->hasMany(FleetInvoiceItem::class, 'fleet_invoice_id');
    }

    public function glAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'gl_account_id');
    }

    public function glJournal()
    {
        return $this->belongsTo(\App\Models\Journal::class, 'gl_journal_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function glTransactions()
    {
        return $this->hasMany(\App\Models\GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'fleet_invoice');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function($q) {
                $q->where('status', 'sent')
                  ->where('due_date', '<', now());
            });
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Hash ID support
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded)) {
            return static::where('id', $decoded[0])->first();
        }
        
        return static::where('id', $value)->first();
    }

    public function getRouteKey()
    {
        return $this->hash_id;
    }

    // Helper methods
    public static function generateInvoiceNumber()
    {
        $prefix = 'FLEET-INV';
        $year = date('Y');
        $month = date('m');
        
        $lastInvoice = self::where('invoice_number', 'like', "{$prefix}-{$year}{$month}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotals()
    {
        $subtotal = $this->items()->sum('amount');
        $discount = $this->discount_amount ?? 0;
        $afterDiscount = $subtotal - $discount;
        $tax = $afterDiscount * ($this->tax_rate / 100);
        $total = $afterDiscount + $tax;

        $this->subtotal = $subtotal;
        $this->tax_amount = $tax;
        $this->total_amount = $total;
        $this->balance_due = $total - ($this->paid_amount ?? 0);
        
        // Update status - but don't change from 'draft' to 'overdue' automatically
        // Draft invoices should remain draft until explicitly sent
        if ($this->balance_due <= 0 && $this->total_amount > 0) {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($this->status !== 'draft' && $this->due_date && $this->due_date->isPast() && $this->balance_due > 0) {
            // Only mark as overdue if status is not 'draft'
            // Draft invoices stay as draft even if due date has passed
            $this->status = 'overdue';
        }

        $this->save();
        return $this;
    }

    /**
     * Convert total_amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return \App\Helpers\AmountInWords::convert($this->total_amount);
    }

    /**
     * Create double-entry GL transactions for fleet invoice
     * Dr: Trip Receivable / Driver Receivable
     * Cr: Trip Revenue / Transport Revenue
     * 
     * @param int|null $receivableAccountId Receivable account ID (required)
     * @param int|null $revenueAccountId Revenue account ID (defaults to invoice's gl_account_id)
     */
    public function createDoubleEntryTransactions($receivableAccountId = null, $revenueAccountId = null)
    {
        $user = auth()->user();
        $userId = $user ? $user->id : ($this->created_by ?? 1);

        // Delete existing transactions for this invoice
        $this->glTransactions()->delete();

        // Use provided accounts or fall back to invoice's gl_account_id
        $revenueAccountId = $revenueAccountId ?? $this->gl_account_id;

        if (!$revenueAccountId || !$receivableAccountId) {
            \Log::warning('FleetInvoice::createDoubleEntryTransactions - Missing accounts', [
                'invoice_id' => $this->id,
                'invoice_number' => $this->invoice_number,
                'revenue_account_id' => $revenueAccountId,
                'receivable_account_id' => $receivableAccountId,
            ]);
            throw new \Exception('Revenue or Receivable account not found. Please set up GL accounts.');
        }

        $transactions = [];

        // 1. Debit Trip Receivable / Driver Receivable
        $transactions[] = [
            'chart_account_id' => $receivableAccountId,
            'customer_id' => $this->customer_id,
            'amount' => $this->total_amount,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'fleet_invoice',
            'date' => $this->invoice_date,
            'description' => "Trip Receivable - Invoice {$this->invoice_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // 2. Credit Trip Revenue / Transport Revenue
        // Group revenue by GL account from invoice items
        $revenueByAccount = [];
        foreach ($this->items as $item) {
            $accountId = $item->gl_account_id ?? $revenueAccountId;
            if (!isset($revenueByAccount[$accountId])) {
                $revenueByAccount[$accountId] = 0;
            }
            $revenueByAccount[$accountId] += $item->amount;
        }

        // If no items or amounts are zero, use total amount on default account
        if (empty($revenueByAccount) || array_sum($revenueByAccount) == 0) {
            $revenueByAccount[$revenueAccountId] = $this->total_amount;
        }

        foreach ($revenueByAccount as $accountId => $amount) {
            if ($amount > 0) {
                $transactions[] = [
                    'chart_account_id' => $accountId,
                    'customer_id' => $this->customer_id,
                    'amount' => $amount,
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'fleet_invoice',
                    'date' => $this->invoice_date,
                    'description' => "Trip Revenue - Invoice {$this->invoice_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $userId,
                ];
            }
        }

        // Create all transactions
        foreach ($transactions as $transaction) {
            try {
                \App\Models\GlTransaction::create($transaction);
            } catch (\Exception $e) {
                \Log::error('FleetInvoice::createDoubleEntryTransactions - Failed to create GL transaction', [
                    'invoice_id' => $this->id,
                    'invoice_number' => $this->invoice_number,
                    'transaction' => $transaction,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        return true;
    }
}
