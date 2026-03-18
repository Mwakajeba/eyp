<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->storeActivityLog('create');
        });

        static::updated(function ($model) {
            // Only log if there are actual changes (Laravel's isDirty check)
            if ($model->isDirty()) {
                // Skip automatic logging if only status-related fields changed
                // (we handle status changes manually with custom descriptions)
                $dirtyFields = array_keys($model->getDirty());
                $statusOnlyFields = ['status', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason', 'current_approval_level', 'submitted_by', 'submitted_at', 'updated_by'];
                
                // Check if only status-related fields changed
                $onlyStatusFieldsChanged = !empty($dirtyFields) && 
                    count(array_diff($dirtyFields, $statusOnlyFields)) === 0;
                
                // Don't auto-log if only status fields changed (we log these manually)
                if (!$onlyStatusFieldsChanged) {
                    $model->storeActivityLog('update');
                }
            }
        });

        static::deleted(function ($model) {
            $model->storeActivityLog('delete');
        });
    }

    protected function storeActivityLog($action)
    {
        $agent = new Agent();

        // Build description with more details
        $description = $this->buildActivityDescription($action);
        
        // Get company_id and branch_id if available
        $companyId = null;
        $branchId = null;
        
        if (isset($this->company_id)) {
            $companyId = $this->company_id;
        } elseif (method_exists($this, 'company')) {
            $company = $this->company;
            $companyId = $company->id ?? null;
        } elseif (Auth::check() && Auth::user()->company_id) {
            $companyId = Auth::user()->company_id;
        }
        
        if (isset($this->branch_id)) {
            $branchId = $this->branch_id;
        } elseif (method_exists($this, 'branch')) {
            $branch = $this->branch;
            $branchId = $branch->id ?? null;
        } elseif (Auth::check() && Auth::user()->branch_id) {
            $branchId = Auth::user()->branch_id;
        }

        try {
            // Ensure user_id is set - use null if not authenticated (system action)
            $userId = Auth::id();
            
            ActivityLog::create([
                'user_id'       => $userId,
                'model'         => class_basename($this),
                'model_id'      => $this->id ?? null,
                'action'        => $action,
                'description'   => $description,
                'ip_address'    => request()->ip() ?? '0.0.0.0',
                'device'        => $agent->device() . ' - ' . $agent->browser(),
                'activity_time' => now(),
                'company_id'    => $companyId,
                'branch_id'     => $branchId,
            ]);
        } catch (\Exception $e) {
            // Silently fail if activity log creation fails to not break main operations
            \Log::warning('Failed to create activity log: ' . $e->getMessage());
        }
    }

    /**
     * Build detailed activity description with comprehensive information for auditors
     */
    protected function buildActivityDescription($action)
    {
        $modelName = class_basename($this);
        $description = ucfirst($action) . "d {$modelName}";
        
        // Add specific details based on model type with comprehensive information
        if ($modelName === 'GlRevaluationHistory') {
            $description = "{$action}d FX Revaluation for {$this->item_type} item";
            if (isset($this->item_ref)) {
                $description .= " ({$this->item_ref})";
            }
            if (isset($this->revaluation_date)) {
                $description .= " dated " . $this->revaluation_date->format('Y-m-d');
            }
            if (isset($this->fcy_amount)) {
                $description .= " | FCY Amount: " . number_format(abs($this->fcy_amount), 2);
            }
            if (isset($this->gain_loss)) {
                $sign = $this->gain_loss >= 0 ? '+' : '';
                $description .= " | Gain/Loss: {$sign}" . number_format($this->gain_loss, 2);
            }
        } elseif ($modelName === 'FxRate') {
            $description = "{$action}d FX Exchange Rate";
            if (isset($this->from_currency) && isset($this->to_currency)) {
                $description .= " {$this->from_currency}/{$this->to_currency}";
            }
            if (isset($this->rate_date)) {
                $description .= " for " . $this->rate_date->format('Y-m-d');
            }
            if (isset($this->spot_rate)) {
                $description .= " | Spot Rate: " . number_format($this->spot_rate, 6);
            }
            if (isset($this->month_end_rate) && $this->month_end_rate) {
                $description .= " | Month-End Rate: " . number_format($this->month_end_rate, 6);
            }
            if (isset($this->average_rate) && $this->average_rate) {
                $description .= " | Average Rate: " . number_format($this->average_rate, 6);
            }
        } elseif ($modelName === 'Journal') {
            $description = "{$action}d Journal Entry";
            if (isset($this->reference)) {
                $description .= " ({$this->reference})";
            }
            if (isset($this->date)) {
                $description .= " dated " . $this->date->format('Y-m-d');
            }
            if (isset($this->total_amount)) {
                $description .= " | Amount: " . number_format($this->total_amount, 2);
            }
            if (isset($this->reference_type)) {
                $description .= " | Type: {$this->reference_type}";
            }
        } elseif ($modelName === 'SalesOrder') {
            $description = "{$action}d Sales Order";
            if (isset($this->order_number)) {
                $description .= " ({$this->order_number})";
            }
            if (isset($this->customer_id) && method_exists($this, 'customer') && $this->customer) {
                $description .= " | Customer: {$this->customer->name}";
            }
            if (isset($this->total_amount)) {
                $description .= " | Total: " . number_format($this->total_amount, 2);
            }
            if (isset($this->order_date)) {
                $description .= " | Date: " . $this->order_date->format('Y-m-d');
            }
            if (isset($this->status)) {
                $description .= " | Status: " . ucfirst(str_replace('_', ' ', $this->status));
            }
        } elseif ($modelName === 'SalesInvoice') {
            $description = "{$action}d Sales Invoice";
            if (isset($this->invoice_number)) {
                $description .= " ({$this->invoice_number})";
            }
            if (isset($this->customer_id) && method_exists($this, 'customer') && $this->customer) {
                $description .= " | Customer: {$this->customer->name}";
            }
            if (isset($this->total_amount)) {
                $description .= " | Total: " . number_format($this->total_amount, 2);
            }
            if (isset($this->invoice_date)) {
                $description .= " | Date: " . $this->invoice_date->format('Y-m-d');
            }
        } elseif ($modelName === 'PurchaseInvoice') {
            $description = "{$action}d Purchase Invoice";
            if (isset($this->invoice_number)) {
                $description .= " ({$this->invoice_number})";
            }
            if (isset($this->supplier_id) && method_exists($this, 'supplier') && $this->supplier) {
                $description .= " | Supplier: {$this->supplier->name}";
            }
            if (isset($this->total_amount)) {
                $description .= " | Total: " . number_format($this->total_amount, 2);
            }
            if (isset($this->invoice_date)) {
                $description .= " | Date: " . $this->invoice_date->format('Y-m-d');
            }
        } elseif ($modelName === 'Payment') {
            $description = "{$action}d Payment";
            if (isset($this->reference)) {
                $description .= " ({$this->reference})";
            }
            $payeeName = $this->payee_name ?? ($this->supplier ? $this->supplier->name : ($this->customer ? $this->customer->name : null));
            if ($payeeName) {
                $description .= " | Payee: {$payeeName}";
            }
            if (isset($this->amount)) {
                $description .= " | Amount: " . number_format($this->amount, 2);
            }
            if (isset($this->date)) {
                $description .= " | Date: " . $this->date->format('Y-m-d');
            }
            if (isset($this->payee_type)) {
                $description .= " | Type: " . ucfirst($this->payee_type);
            }
        } elseif ($modelName === 'Receipt') {
            $description = "{$action}d Receipt";
            if (isset($this->reference)) {
                $description .= " ({$this->reference})";
            }
            $payerName = $this->payer_name ?? ($this->customer ? $this->customer->name : ($this->supplier ? $this->supplier->name : null));
            if ($payerName) {
                $description .= " | Payer: {$payerName}";
            }
            if (isset($this->amount)) {
                $description .= " | Amount: " . number_format($this->amount, 2);
            }
            if (isset($this->date)) {
                $description .= " | Date: " . $this->date->format('Y-m-d');
            }
            if (isset($this->payer_type)) {
                $description .= " | Type: " . ucfirst($this->payer_type);
            }
        } elseif ($modelName === 'CreditNote') {
            $description = "{$action}d Credit Note";
            if (isset($this->credit_note_number)) {
                $description .= " ({$this->credit_note_number})";
            }
            if (isset($this->customer_id) && method_exists($this, 'customer') && $this->customer) {
                $description .= " | Customer: {$this->customer->name}";
            }
            if (isset($this->total_amount)) {
                $description .= " | Amount: " . number_format($this->total_amount, 2);
            }
            if (isset($this->credit_note_date)) {
                $description .= " | Date: " . $this->credit_note_date->format('Y-m-d');
            }
        } elseif ($modelName === 'CashSale') {
            $description = "{$action}d Cash Sale";
            if (isset($this->sale_number)) {
                $description .= " ({$this->sale_number})";
            }
            if (isset($this->customer_id) && method_exists($this, 'customer') && $this->customer) {
                $description .= " | Customer: {$this->customer->name}";
            }
            if (isset($this->total_amount)) {
                $description .= " | Total: " . number_format($this->total_amount, 2);
            }
            if (isset($this->sale_date)) {
                $description .= " | Date: " . $this->sale_date->format('Y-m-d');
            }
            if (isset($this->payment_method)) {
                $description .= " | Payment: " . ucfirst(str_replace('_', ' ', $this->payment_method));
            }
        } elseif ($modelName === 'PosSale') {
            $description = "{$action}d POS Sale";
            if (isset($this->pos_number)) {
                $description .= " ({$this->pos_number})";
            }
            if (isset($this->customer_id) && method_exists($this, 'customer') && $this->customer) {
                $description .= " | Customer: {$this->customer->name}";
            } elseif (isset($this->customer_name)) {
                $description .= " | Customer: {$this->customer_name}";
            }
            if (isset($this->total_amount)) {
                $description .= " | Total: " . number_format($this->total_amount, 2);
            }
            if (isset($this->sale_date)) {
                $description .= " | Date: " . $this->sale_date->format('Y-m-d');
            }
            if (isset($this->payment_method)) {
                $description .= " | Payment: " . ucfirst(str_replace('_', ' ', $this->payment_method));
            }
        } elseif ($modelName === 'Delivery') {
            $description = "{$action}d Delivery";
            if (isset($this->delivery_number)) {
                $description .= " ({$this->delivery_number})";
            }
            if (isset($this->customer_id) && method_exists($this, 'customer') && $this->customer) {
                $description .= " | Customer: {$this->customer->name}";
            }
            if (isset($this->delivery_date)) {
                $description .= " | Date: " . $this->delivery_date->format('Y-m-d');
            }
            if (isset($this->status)) {
                $description .= " | Status: " . ucfirst(str_replace('_', ' ', $this->status));
            }
        } elseif ($modelName === 'SalesProforma') {
            $description = "{$action}d Sales Proforma";
            if (isset($this->proforma_number)) {
                $description .= " ({$this->proforma_number})";
            }
            if (isset($this->customer_id) && method_exists($this, 'customer') && $this->customer) {
                $description .= " | Customer: {$this->customer->name}";
            }
            if (isset($this->total_amount)) {
                $description .= " | Total: " . number_format($this->total_amount, 2);
            }
            if (isset($this->proforma_date)) {
                $description .= " | Date: " . $this->proforma_date->format('Y-m-d');
            }
            if (isset($this->status)) {
                $description .= " | Status: " . ucfirst(str_replace('_', ' ', $this->status));
            }
        } elseif ($modelName === 'CashPurchase') {
            $description = "{$action}d Cash Purchase";
            if (isset($this->supplier_id) && method_exists($this, 'supplier') && $this->supplier) {
                $description .= " | Supplier: {$this->supplier->name}";
            }
            if (isset($this->total_amount)) {
                $description .= " | Total: " . number_format($this->total_amount, 2);
            }
            if (isset($this->purchase_date)) {
                $description .= " | Date: " . $this->purchase_date->format('Y-m-d');
            }
            if (isset($this->payment_method)) {
                $description .= " | Payment: " . ucfirst(str_replace('_', ' ', $this->payment_method));
            }
            if (isset($this->currency)) {
                $description .= " | Currency: {$this->currency}";
            }
        } elseif ($modelName === 'StoreRequisition') {
            $description = "{$action}d Store Requisition";
            if (isset($this->requisition_number)) {
                $description .= " ({$this->requisition_number})";
            }
            if (isset($this->status)) {
                $description .= " | Status: " . ucfirst(str_replace('_', ' ', $this->status));
            }
            if (isset($this->requisition_date)) {
                $description .= " | Date: " . $this->requisition_date->format('Y-m-d');
            }
        } elseif ($modelName === 'ImprestRequest') {
            $description = "{$action}d Imprest Request";
            if (isset($this->request_number)) {
                $description .= " ({$this->request_number})";
            }
            if (isset($this->amount)) {
                $description .= " | Amount: " . number_format($this->amount, 2);
            }
            if (isset($this->request_date)) {
                $description .= " | Date: " . $this->request_date->format('Y-m-d');
            }
        } elseif ($modelName === 'Asset') {
            $description = "{$action}d Asset";
            if (isset($this->code)) {
                $description .= " ({$this->code})";
            }
            if (isset($this->name)) {
                $description .= " - {$this->name}";
            }
            if (isset($this->acquisition_cost)) {
                $description .= " | Cost: " . number_format($this->acquisition_cost, 2);
            }
        } elseif ($modelName === 'Movement') {
            // Handle Inventory Movement activity logs
            $movementTypeLabels = [
                'opening_balance' => 'Opening Balance',
                'transfer_in' => 'Transfer In',
                'transfer_out' => 'Transfer Out',
                'sold' => 'Sold',
                'purchased' => 'Purchased',
                'adjustment_in' => 'Adjustment In',
                'adjustment_out' => 'Adjustment Out',
                'write_off' => 'Write Off',
            ];
            
            $movementType = $movementTypeLabels[$this->movement_type] ?? ucfirst($this->movement_type);
            $description = "{$action}d Inventory Movement - {$movementType}";
            
            if (isset($this->item_id) && method_exists($this, 'item') && $this->item) {
                $description .= " | Item: {$this->item->name} ({$this->item->code})";
            }
            
            if (isset($this->quantity)) {
                $description .= " | Quantity: " . number_format($this->quantity, 2);
                if (isset($this->item) && $this->item && $this->item->unit_of_measure) {
                    $description .= " " . $this->item->unit_of_measure;
                }
            }
            
            if (isset($this->total_cost)) {
                $description .= " | Total Cost: " . number_format($this->total_cost, 2);
            }
            
            if (isset($this->reference)) {
                $description .= " | Reference: {$this->reference}";
            }
            
            if (isset($this->reason)) {
                $description .= " | Reason: {$this->reason}";
            }
            
            if (isset($this->movement_date)) {
                $description .= " | Date: " . (is_string($this->movement_date) ? $this->movement_date : $this->movement_date->format('Y-m-d'));
            }
            
            if (isset($this->location_id) && method_exists($this, 'location') && $this->location) {
                $description .= " | Location: {$this->location->name}";
            }
        } elseif ($modelName === 'InvestmentTrade') {
            $description = "{$action}d Investment Trade";
            if (isset($this->investment_id) && method_exists($this, 'investment') && $this->investment) {
                $description .= " for {$this->investment->instrument_code}";
            }
            if (isset($this->trade_type)) {
                $description .= " | Type: {$this->trade_type}";
            }
            if (isset($this->trade_date)) {
                $description .= " | Date: " . $this->trade_date->format('Y-m-d');
            }
            if (isset($this->gross_amount)) {
                $description .= " | Amount: " . number_format($this->gross_amount, 2);
            }
            // ECL-specific logging
            if (isset($this->ecl_amount)) {
                $description .= " | ECL: " . number_format($this->ecl_amount, 2);
            }
            if (isset($this->stage)) {
                $description .= " | Stage: {$this->stage}";
            }
            // Fair value logging
            if (isset($this->fair_value)) {
                $description .= " | Fair Value: " . number_format($this->fair_value, 2);
                if (isset($this->fair_value_source)) {
                    $description .= " ({$this->fair_value_source})";
                }
            }
        } elseif ($modelName === 'InvestmentMaster') {
            $description = "{$action}d Investment";
            if (isset($this->instrument_code)) {
                $description .= " ({$this->instrument_code})";
            }
            if (isset($this->instrument_type)) {
                $description .= " | Type: " . str_replace('_', ' ', $this->instrument_type);
            }
            if (isset($this->issuer)) {
                $description .= " | Issuer: {$this->issuer}";
            }
            if (isset($this->nominal_amount)) {
                $description .= " | Amount: " . number_format($this->nominal_amount, 2);
            }
        } elseif (method_exists($this, 'getDisplayName')) {
            $description .= " - " . $this->getDisplayName();
        } elseif (isset($this->name)) {
            $description .= " - {$this->name}";
        } elseif (isset($this->reference)) {
            $description .= " - {$this->reference}";
        } elseif (isset($this->order_number)) {
            $description .= " - {$this->order_number}";
        } elseif (isset($this->number)) {
            $description .= " - {$this->number}";
        } else {
            $description .= " (ID: {$this->id})";
        }
        
        return $description;
    }

    /**
     * Manually log an activity for this model
     * Use this for actions that don't trigger model events (post, approve, reject, reverse, lock, unlock, etc.)
     * 
     * @param string $action The action being performed (post, approve, reject, reverse, lock, unlock, activate, deactivate, etc.)
     * @param string|null $customDescription Optional custom description. If not provided, will use buildActivityDescription
     * @param array $additionalData Optional additional data to include in description
     * @return void
     */
    public function logActivity($action, $customDescription = null, $additionalData = [])
    {
        $agent = new Agent();

        // Build description
        if ($customDescription) {
            $description = $customDescription;
        } else {
            $description = $this->buildActivityDescription($action);
        }

        // Add additional data to description if provided
        if (!empty($additionalData)) {
            $extraInfo = [];
            foreach ($additionalData as $key => $value) {
                if ($value !== null) {
                    $extraInfo[] = ucfirst(str_replace('_', ' ', $key)) . ": {$value}";
                }
            }
            if (!empty($extraInfo)) {
                $description .= " | " . implode(", ", $extraInfo);
            }
        }
        
        // Get company_id and branch_id if available
        $companyId = null;
        $branchId = null;
        
        if (isset($this->company_id)) {
            $companyId = $this->company_id;
        } elseif (method_exists($this, 'company')) {
            $company = $this->company;
            $companyId = $company->id ?? null;
        } elseif (Auth::check() && Auth::user()->company_id) {
            $companyId = Auth::user()->company_id;
        }
        
        if (isset($this->branch_id)) {
            $branchId = $this->branch_id;
        } elseif (method_exists($this, 'branch')) {
            $branch = $this->branch;
            $branchId = $branch->id ?? null;
        } elseif (Auth::check() && Auth::user()->branch_id) {
            $branchId = Auth::user()->branch_id;
        }

        try {
            ActivityLog::create([
                'user_id'       => Auth::id(),
                'model'         => class_basename($this),
                'model_id'      => $this->id ?? null,
                'action'        => $action,
                'description'   => $description,
                'ip_address'    => request()->ip(),
                'device'        => $agent->device() . ' - ' . $agent->browser(),
                'activity_time' => now(),
                'company_id'    => $companyId,
                'branch_id'     => $branchId,
            ]);
        } catch (\Exception $e) {
            // Silently fail if activity log creation fails to not break main operations
            \Log::warning('Failed to create activity log: ' . $e->getMessage());
        }
    }
}
