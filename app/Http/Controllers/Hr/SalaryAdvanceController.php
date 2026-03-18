<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\GlTransaction;
use App\Models\Hr\Employee;
use App\Models\Hr\PayrollChartAccount;
use App\Models\Hr\SalaryAdvance;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Traits\TransactionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class SalaryAdvanceController extends Controller
{
    use TransactionHelper;

    /**
     * Display a listing of salary advances
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {
            $salaryAdvances = SalaryAdvance::with(['employee', 'bankAccount', 'user'])
                ->withCount('repayments')
                ->where('company_id', $user->company_id)
                ->orderBy('created_at', 'desc');

            return DataTables::of($salaryAdvances)
                ->addIndexColumn()
                ->addColumn('reference_display', function ($advance) {
                    return '<strong class="text-primary">' . e($advance->reference) . '</strong>';
                })
                ->addColumn('date_display', function ($advance) {
                    return $advance->date ? $advance->date->format('M d, Y') : 'N/A';
                })
                ->addColumn('employee_display', function ($advance) {
                    if ($advance->employee) {
                        $html = '<div><strong>' . e($advance->employee->full_name) . '</strong>';
                        if ($advance->employee->employee_number) {
                            $html .= '<br><small class="text-muted">' . e($advance->employee->employee_number) . '</small>';
                        }
                        $html .= '</div>';
                        return $html;
                    }
                    return 'N/A';
                })
                ->addColumn('bank_account_display', function ($advance) {
                    return $advance->bankAccount ? e($advance->bankAccount->name) : 'N/A';
                })
                ->addColumn('amount_display', function ($advance) {
                    return '<strong class="text-success">TZS ' . number_format($advance->amount, 2) . '</strong>';
                })
                ->addColumn('monthly_deduction_display', function ($advance) {
                    return '<strong>TZS ' . number_format($advance->monthly_deduction, 2) . '</strong>';
                })
                ->addColumn('status_badge', function ($advance) {
                    if ($advance->is_active) {
                        return '<span class="badge bg-success">Active</span>';
                    }
                    return '<span class="badge bg-secondary">Inactive</span>';
                })
                ->addColumn('action', function ($advance) {
                    $viewBtn = '<a href="' . route('hr.salary-advances.show', $advance) . '" class="btn btn-sm btn-outline-info me-1" title="View Details"><i class="bx bx-show"></i></a>';
                    
                    if ($advance->is_active) {
                        $editBtn = '<a href="' . route('hr.salary-advances.edit', $advance) . '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bx bx-edit"></i></a>';
                        $deleteBtn = ($advance->repayments_count ?? 0) === 0
                            ? '<button type="button" onclick="deleteAdvance(' . $advance->id . ', \'' . e($advance->reference) . '\')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bx bx-trash"></i></button>'
                            : '';
                        return $viewBtn . $editBtn . $deleteBtn;
                    }
                    
                    return $viewBtn;
                })
                ->rawColumns(['reference_display', 'employee_display', 'amount_display', 'monthly_deduction_display', 'status_badge', 'action'])
                ->make(true);
        }

        // Get statistics for the view
        $totalAdvances = SalaryAdvance::where('company_id', $user->company_id)->count();
        $activeAdvances = SalaryAdvance::where('company_id', $user->company_id)->where('is_active', true)->count();
        $inactiveAdvances = SalaryAdvance::where('company_id', $user->company_id)->where('is_active', false)->count();
        $totalAmount = SalaryAdvance::where('company_id', $user->company_id)->sum('amount');

        $statistics = [
            'total' => $totalAdvances,
            'active' => $activeAdvances,
            'inactive' => $inactiveAdvances,
            'total_amount' => $totalAmount,
        ];

        return view('hr-payroll.salary-advances.index', compact('statistics'));
    }

    /**
     * Show the form for creating a new salary advance
     */
    public function create()
    {
        $user = Auth::user();

        // Get employees for the current company
        $employees = Employee::where('company_id', $user->company_id)
            ->orderBy('first_name')
            ->get();

        // Get bank accounts for the current company
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        // Get branches for the current company
        $branches = Branch::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.salary-advances.create', compact('employees', 'bankAccounts', 'branches'));
    }

    /**
     * Store a newly created salary advance
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'monthly_deduction' => 'required|numeric|min:0.01',
            'repayment_type' => 'required|in:payroll,manual,both',
            'reason' => 'required|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            return $this->runTransaction(function () use ($request) {
                $user = Auth::user();
                $branchId = $request->branch_id ?? session('branch_id') ?? $user->branch_id;

                // Generate unique reference
                $reference = 'SA-' . strtoupper(uniqid());

                // Get payroll chart account for the salary advance receivable
                $chartAccounts = PayrollChartAccount::where('company_id', $user->company_id)->first();
                
                if (!$chartAccounts || !$chartAccounts->salary_advance_receivable_account_id) {
                    throw new \Exception('Salary advance receivable account not configured in payroll chart accounts.');
                }

                // Get bank account
                $bankAccount = BankAccount::find($request->bank_account_id);
                if (!$bankAccount) {
                    throw new \Exception('Bank account not found.');
                }

                // Create salary advance
                $salaryAdvance = SalaryAdvance::create([
                    'company_id' => $user->company_id,
                    'employee_id' => $request->employee_id,
                    'bank_account_id' => $request->bank_account_id,
                    'user_id' => $user->id,
                    'branch_id' => $branchId,
                    'reference' => $reference,
                    'date' => $request->date,
                    'amount' => $request->amount,
                    'monthly_deduction' => $request->monthly_deduction,
                    'repayment_type' => $request->repayment_type,
                    'reason' => $request->reason,
                    'is_active' => true,
                ]);

                $employee = Employee::find($request->employee_id);

                // Create payment record
                $payment = Payment::create([
                    'reference' => $reference,
                    'reference_type' => 'salary_advance',
                    'reference_number' => $salaryAdvance->id,
                    'amount' => $request->amount,
                    'wht_treatment' => 'NONE',
                    'wht_rate' => 0,
                    'wht_amount' => 0,
                    'net_payable' => $request->amount,
                    'total_cost' => $request->amount,
                    'vat_mode' => 'NONE',
                    'vat_amount' => 0,
                    'base_amount' => $request->amount,
                    'date' => $request->date,
                    'description' => "Salary advance for {$employee->full_name} - {$request->reason}",
                    'bank_account_id' => $request->bank_account_id,
                    'payee_type' => 'other',
                    'payee_name' => $employee->full_name,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                    'approved' => true,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                // Create payment item for salary advance receivable (Debit)
                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'chart_account_id' => $chartAccounts->salary_advance_receivable_account_id,
                    'amount' => $request->amount,
                    'wht_treatment' => 'NONE',
                    'wht_rate' => 0,
                    'wht_amount' => 0,
                    'base_amount' => $request->amount,
                    'net_payable' => $request->amount,
                    'total_cost' => $request->amount,
                    'vat_mode' => 'NONE',
                    'vat_amount' => 0,
                    'description' => "Salary advance receivable - {$employee->full_name}",
                ]);

                // Create GL transactions via Payment model
                // This will automatically create:
                // - DR Salary Advance Receivable
                // - CR Bank Account
                $payment->createGlTransactions();

                // Store payment_id in salary_advance for reference
                $salaryAdvance->update(['payment_id' => $payment->id]);

                return redirect()->route('hr.salary-advances.show', $salaryAdvance)
                    ->with('success', 'Salary advance created successfully.');
            });
        } catch (\Exception $e) {
            Log::error('Failed to create salary advance: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create salary advance: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified salary advance
     */
    public function show(SalaryAdvance $salaryAdvance)
    {
        $salaryAdvance->load(['employee', 'bankAccount', 'user', 'branch', 'repayments.user', 'repayments.payroll']);

        $repayments = $salaryAdvance->repayments()->orderByDesc('date')->get();

        $user = Auth::user();
        $bankAccounts = BankAccount::with('chartAccount')
            ->where(function ($query) use ($user) {
                $query->where('company_id', $user->company_id)
                    ->orWhereHas('chartAccount.accountClassGroup', function ($q) use ($user) {
                        $q->where('company_id', $user->company_id);
                    });
            })
            ->orderBy('name')
            ->get();

        return view('hr-payroll.salary-advances.show', compact('salaryAdvance', 'repayments', 'bankAccounts'));
    }

    /**
     * Export employee statement as PDF (sales-invoice style).
     */
    public function statementPdf(SalaryAdvance $salaryAdvance)
    {
        $user = Auth::user();
        if ($salaryAdvance->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to salary advance.');
        }

        $salaryAdvance->load([
            'company',
            'branch',
            'employee.department',
            'employee.position',
            'bankAccount',
            'repayments.bankAccount',
            'repayments.user',
        ]);

        $bankAccounts = BankAccount::with('chartAccount')
            ->where(function ($query) use ($user) {
                $query->where('company_id', $user->company_id)
                    ->orWhereHas('chartAccount.accountClassGroup', function ($q) use ($user) {
                        $q->where('company_id', $user->company_id);
                    });
            })
            ->orderBy('name')
            ->get();

        $pageSize = strtoupper((string) (\App\Models\SystemSetting::getValue('document_page_size', 'A4')));
        $orientation = strtolower((string) (\App\Models\SystemSetting::getValue('document_orientation', 'portrait')));
        $marginTopStr = \App\Models\SystemSetting::getValue('document_margin_top', '15mm');
        $marginRightStr = \App\Models\SystemSetting::getValue('document_margin_right', '15mm');
        $marginBottomStr = \App\Models\SystemSetting::getValue('document_margin_bottom', '15mm');
        $marginLeftStr = \App\Models\SystemSetting::getValue('document_margin_left', '15mm');
        $convertToMm = function ($value) {
            if (is_numeric($value)) {
                return (float) $value;
            }
            $numeric = (float) str_replace(['cm', 'mm', 'pt', 'px', 'in'], '', $value);
            if (strpos($value, 'cm') !== false) {
                return $numeric * 10;
            }
            return $numeric;
        };

        try {
            $pdf = \PDF::loadView('hr-payroll.salary-advances.statement-pdf', compact('salaryAdvance', 'bankAccounts'));
            $pdf->setPaper($pageSize, $orientation);
            $pdf->setOptions([
                'margin-top' => $convertToMm($marginTopStr),
                'margin-right' => $convertToMm($marginRightStr),
                'margin-bottom' => $convertToMm($marginBottomStr),
                'margin-left' => $convertToMm($marginLeftStr),
            ]);
            $employeeName = $salaryAdvance->employee ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $salaryAdvance->employee->full_name) : 'Employee';
            $filename = 'Salary_Advance_Statement_' . $employeeName . '_' . $salaryAdvance->reference . '_' . date('Y-m-d') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Salary advance statement PDF error: ' . $e->getMessage());
            return redirect()->route('hr.salary-advances.show', $salaryAdvance)
                ->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified salary advance
     */
    public function edit(SalaryAdvance $salaryAdvance)
    {
        $user = Auth::user();

        // Check if user can edit this salary advance
        if ($salaryAdvance->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to salary advance.');
        }

        // Check if salary advance can be edited (only active advances)
        if (!$salaryAdvance->is_active) {
            return redirect()->route('hr.salary-advances.show', $salaryAdvance)
                ->with('error', 'Only active salary advances can be edited.');
        }

        // Get employees for the current company
        $employees = Employee::where('company_id', $user->company_id)
            ->orderBy('first_name')
            ->get();

        // Get bank accounts for the current company
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        // Get branches for the current company
        $branches = Branch::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.salary-advances.edit', compact('salaryAdvance', 'employees', 'bankAccounts', 'branches'));
    }

    /**
     * Update the specified salary advance
     */
    public function update(Request $request, SalaryAdvance $salaryAdvance)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'monthly_deduction' => 'required|numeric|min:0.01',
            'repayment_type' => 'required|in:payroll,manual,both',
            'reason' => 'required|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user = Auth::user();

        // Check if user can update this salary advance
        if ($salaryAdvance->company_id !== $user->company_id) {
            Log::error('Unauthorized access to salary advance: ' . $salaryAdvance->id);
            abort(403, 'Unauthorized access to salary advance.');
        }

        // Check if salary advance can be updated (only active advances)
        if (!$salaryAdvance->is_active) {
            Log::error('Only active salary advances can be updated: ' . $salaryAdvance->id);
            return redirect()->route('hr.salary-advances.show', $salaryAdvance)
                ->with('error', 'Only active salary advances can be updated.');
        }

        try {
            return $this->runTransaction(function () use ($request, $salaryAdvance, $user) {
                $branchId = $request->branch_id ?? session('branch_id') ?? $user->branch_id;

                // Get payroll chart account for the salary advance receivable
                $chartAccounts = PayrollChartAccount::where('company_id', $user->company_id)->first();
                
                if (!$chartAccounts || !$chartAccounts->salary_advance_receivable_account_id) {
                    throw new \Exception('Salary advance receivable account not configured in payroll chart accounts.');
                }

                // Get bank account
                $bankAccount = BankAccount::find($request->bank_account_id);
                if (!$bankAccount) {
                    throw new \Exception('Bank account not found.');
                }

                // Get the related payment and delete old transactions
                $payment = Payment::where('reference', $salaryAdvance->reference)
                    ->where('reference_type', 'salary_advance')
                    ->first();

                if ($payment) {
                    // Delete existing GL transactions
                    GlTransaction::where('transaction_id', $payment->id)
                        ->where('transaction_type', 'payment')
                        ->delete();

                    // Delete existing payment items
                    PaymentItem::where('payment_id', $payment->id)->delete();

                    // Delete the payment
                    $payment->delete();
                }

                $employee = Employee::find($request->employee_id);

                // Update salary advance
                $salaryAdvance->update([
                    'employee_id' => $request->employee_id,
                    'bank_account_id' => $request->bank_account_id,
                    'date' => $request->date,
                    'amount' => $request->amount,
                    'monthly_deduction' => $request->monthly_deduction,
                    'repayment_type' => $request->repayment_type,
                    'reason' => $request->reason,
                    'branch_id' => $branchId,
                ]);

                // Create new payment
                $newPayment = Payment::create([
                    'reference' => $salaryAdvance->reference,
                    'reference_type' => 'salary_advance',
                    'reference_number' => $salaryAdvance->id,
                    'amount' => $request->amount,
                    'wht_treatment' => 'NONE',
                    'wht_rate' => 0,
                    'wht_amount' => 0,
                    'net_payable' => $request->amount,
                    'total_cost' => $request->amount,
                    'vat_mode' => 'NONE',
                    'vat_amount' => 0,
                    'base_amount' => $request->amount,
                    'date' => $request->date,
                    'description' => "Salary advance for {$employee->full_name} - {$request->reason}",
                    'bank_account_id' => $request->bank_account_id,
                    'payee_type' => 'other',
                    'payee_name' => $employee->full_name,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                    'approved' => true,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                // Create new payment item
                PaymentItem::create([
                    'payment_id' => $newPayment->id,
                    'chart_account_id' => $chartAccounts->salary_advance_receivable_account_id,
                    'amount' => $request->amount,
                    'wht_treatment' => 'NONE',
                    'wht_rate' => 0,
                    'wht_amount' => 0,
                    'base_amount' => $request->amount,
                    'net_payable' => $request->amount,
                    'total_cost' => $request->amount,
                    'vat_mode' => 'NONE',
                    'vat_amount' => 0,
                    'description' => "Salary advance receivable - {$employee->full_name}",
                ]);

                // Create GL transactions via Payment model
                // This will automatically create:
                // - DR Salary Advance Receivable
                // - CR Bank Account
                $newPayment->createGlTransactions();

                // Update payment_id reference
                $salaryAdvance->update(['payment_id' => $newPayment->id]);

                return redirect()->route('hr.salary-advances.show', $salaryAdvance)
                    ->with('success', 'Salary advance updated successfully.');
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update salary advance: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified salary advance
     */
    public function destroy(SalaryAdvance $salaryAdvance)
    {
        $user = Auth::user();

        // Check if user can delete this salary advance
        if ($salaryAdvance->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to salary advance.');
        }

        // Check if salary advance can be deleted (only active advances)
        if (!$salaryAdvance->is_active) {
            return redirect()->route('hr.salary-advances.show', $salaryAdvance)
                ->with('error', 'Only active salary advances can be deleted.');
        }

        // Do not delete if it has any repayments
        if ($salaryAdvance->repayments()->exists()) {
            return redirect()->route('hr.salary-advances.show', $salaryAdvance)
                ->with('error', 'Cannot delete a salary advance that has repayments. Only advances with no repayments can be deleted.');
        }

        try {
            return $this->runTransaction(function () use ($salaryAdvance) {
                // Get the related payment and transactions
                $payment = Payment::where('reference', $salaryAdvance->reference)
                    ->where('reference_type', 'salary_advance')
                    ->first();

                if ($payment) {
                    // Delete existing GL transactions
                    GlTransaction::where('transaction_id', $payment->id)
                        ->where('transaction_type', 'payment')
                        ->delete();

                    // Delete existing payment items
                    PaymentItem::where('payment_id', $payment->id)->delete();

                    // Delete the payment
                    $payment->delete();
                }

                // Delete the salary advance
                $salaryAdvance->delete();

                return redirect()->route('hr.salary-advances.index')
                    ->with('success', 'Salary advance deleted successfully.');
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete salary advance: ' . $e->getMessage()]);
        }
    }

    /**
     * Show bulk repayment page (download template + upload CSV)
     */
    public function bulkRepayment()
    {
        $user = Auth::user();
        $bankAccounts = BankAccount::with('chartAccount')
            ->where(function ($query) use ($user) {
                $query->where('company_id', $user->company_id)
                    ->orWhereHas('chartAccount.accountClassGroup', function ($q) use ($user) {
                        $q->where('company_id', $user->company_id);
                    });
            })
            ->orderBy('name')
            ->get();
        $activeCount = SalaryAdvance::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereRaw('amount > (SELECT COALESCE(SUM(amount), 0) FROM hr_salary_advance_repayments WHERE salary_advance_id = hr_salary_advances.id)')
            ->count();

        return view('hr-payroll.salary-advances.bulk-repayment', compact('bankAccounts', 'activeCount'));
    }

    /**
     * Download CSV template with all employees who have an active salary advance (remaining balance > 0).
     * User fills the amount_to_repay column and uploads back.
     */
    public function downloadBulkRepaymentTemplate(): StreamedResponse
    {
        $user = Auth::user();
        $advances = SalaryAdvance::with(['employee'])
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('reference')
            ->get()
            ->filter(function ($advance) {
                return $advance->remaining_balance > 0;
            });

        $filename = 'salary_advance_bulk_repayment_' . date('Y-m-d_His') . '.csv';

        return new StreamedResponse(function () use ($advances) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'advance_reference',
                'employee_number',
                'employee_name',
                'remaining_balance',
                'amount_to_repay',
            ]);
            foreach ($advances as $advance) {
                fputcsv($out, [
                    $advance->reference,
                    $advance->employee ? $advance->employee->employee_number : '',
                    $advance->employee ? $advance->employee->full_name : '',
                    number_format($advance->remaining_balance, 2, '.', ''),
                    '', // user fills this
                ]);
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Process uploaded bulk repayment CSV.
     */
    public function processBulkRepayment(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $bankAccount = BankAccount::where('id', $request->bank_account_id)
            ->where(function ($q) use ($user) {
                $q->where('company_id', $user->company_id)
                    ->orWhereHas('chartAccount.accountClassGroup', function ($q2) use ($user) {
                        $q2->where('company_id', $user->company_id);
                    });
            })
            ->first();
        if (!$bankAccount) {
            return redirect()->back()->withErrors(['error' => 'Invalid bank account.'])->withInput();
        }

        $chartAccounts = PayrollChartAccount::where('company_id', $user->company_id)->first();
        if (!$chartAccounts || !$chartAccounts->salary_advance_receivable_account_id) {
            return redirect()->back()->withErrors(['error' => 'Salary advance receivable account not configured in payroll chart accounts.'])->withInput();
        }

        $path = $request->file('csv_file')->getRealPath();
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle);
            $expected = ['advance_reference', 'employee_number', 'employee_name', 'remaining_balance', 'amount_to_repay'];
            if (array_map('strtolower', array_map('trim', $header)) !== array_map('strtolower', $expected)) {
                fclose($handle);
                return redirect()->back()->withErrors(['error' => 'Invalid CSV format. Download the template and use the same column headers.'])->withInput();
            }
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) >= 5) {
                    $rows[] = [
                        'advance_reference' => trim($data[0] ?? ''),
                        'amount_to_repay' => trim($data[4] ?? ''),
                    ];
                }
            }
            fclose($handle);
        }

        $processed = 0;
        $errors = [];
        $repaymentDate = $request->date;

        try {
            return $this->runTransaction(function () use ($request, $user, $chartAccounts, $rows, $repaymentDate, &$processed, &$errors) {
                foreach ($rows as $index => $row) {
                    $ref = $row['advance_reference'];
                    $amount = isset($row['amount_to_repay']) && $row['amount_to_repay'] !== '' ? (float) preg_replace('/[^0-9.]/', '', $row['amount_to_repay']) : 0;
                    if ($ref === '' || $amount <= 0) {
                        continue;
                    }

                    $salaryAdvance = SalaryAdvance::where('company_id', $user->company_id)
                        ->where('reference', $ref)
                        ->first();

                    if (!$salaryAdvance) {
                        $errors[] = "Row " . ($index + 2) . ": Advance reference \"{$ref}\" not found.";
                        continue;
                    }
                    if (!$salaryAdvance->is_active) {
                        $errors[] = "Row " . ($index + 2) . ": Advance \"{$ref}\" is not active.";
                        continue;
                    }

                    $maxAmount = round((float) $salaryAdvance->remaining_balance, 2);
                    if ($amount > $maxAmount) {
                        $errors[] = "Row " . ($index + 2) . ": Amount {$amount} exceeds remaining balance {$maxAmount} for advance \"{$ref}\".";
                        continue;
                    }

                    $repayment = $salaryAdvance->recordRepayment(
                        $amount,
                        $repaymentDate,
                        null,
                        'manual',
                        $request->bank_account_id,
                        'Bulk repayment'
                    );
                    if (!$repayment) {
                        $errors[] = "Row " . ($index + 2) . ": Failed to record repayment for \"{$ref}\".";
                        continue;
                    }

                    $receiptReference = 'RCP-' . strtoupper(Str::random(8));
                    $receipt = \App\Models\Receipt::create([
                        'company_id' => $salaryAdvance->company_id,
                        'branch_id' => $salaryAdvance->branch_id,
                        'user_id' => Auth::id(),
                        'bank_account_id' => $request->bank_account_id,
                        'date' => $repaymentDate,
                        'amount' => $amount,
                        'reference' => $receiptReference,
                        'reference_type' => 'salary_advance_repayment',
                        'reference_number' => $repayment->reference,
                        'description' => "Manual repayment for salary advance {$salaryAdvance->reference} - {$salaryAdvance->employee->full_name}",
                        'payee_type' => 'employee',
                        'payee_id' => $salaryAdvance->employee_id,
                        'payee_name' => $salaryAdvance->employee->full_name,
                        'employee_id' => $salaryAdvance->employee_id,
                        'status' => 'approved',
                        'approved' => true,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);
                    \App\Models\ReceiptItem::create([
                        'receipt_id' => $receipt->id,
                        'chart_account_id' => $chartAccounts->salary_advance_receivable_account_id,
                        'amount' => $amount,
                        'base_amount' => $amount,
                        'vat_amount' => 0,
                        'description' => "Salary advance repayment - {$salaryAdvance->employee->full_name}",
                    ]);
                    if (method_exists($receipt, 'createGlTransactions')) {
                        $receipt->createGlTransactions();
                    }
                    $processed++;
                }

                $message = $processed . ' repayment(s) recorded successfully.';
                if (count($errors) > 0) {
                    $message .= ' Some rows had errors: ' . implode(' ', array_slice($errors, 0, 5));
                    if (count($errors) > 5) {
                        $message .= ' ... and ' . (count($errors) - 5) . ' more.';
                    }
                }
                return redirect()->route('hr.salary-advances.index')->with('success', $message)->with('bulk_errors', $errors);
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Bulk repayment failed: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Record a manual repayment for a salary advance
     */
    public function recordManualRepayment(Request $request, SalaryAdvance $salaryAdvance)
    {
        $maxAmount = round((float) $salaryAdvance->remaining_balance, 2);
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $maxAmount,
            'date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            return $this->runTransaction(function () use ($request, $salaryAdvance) {
                // Get payroll chart account for the salary advance receivable
                $chartAccounts = PayrollChartAccount::where('company_id', $salaryAdvance->company_id)->first();
                if (!$chartAccounts || !$chartAccounts->salary_advance_receivable_account_id) {
                    throw new \Exception('Salary advance receivable account not configured in payroll chart accounts.');
                }

                // Record Repayment using model method
                $repayment = $salaryAdvance->recordRepayment(
                    $request->amount,
                    $request->date,
                    null,
                    'manual',
                    $request->bank_account_id,
                    $request->notes
                );

                if (!$repayment) {
                    throw new \Exception('Failed to record repayment.');
                }

                // Record Receipt for accounting
                $receiptReference = 'RCP-' . strtoupper(Str::random(8));
                $receipt = \App\Models\Receipt::create([
                    'company_id' => $salaryAdvance->company_id,
                    'branch_id' => $salaryAdvance->branch_id,
                    'user_id' => Auth::id(),
                    'bank_account_id' => $request->bank_account_id,
                    'date' => $request->date,
                    'amount' => $request->amount,
                    'reference' => $receiptReference,
                    'reference_type' => 'salary_advance_repayment',
                    'reference_number' => $repayment->reference,
                    'description' => "Manual repayment for salary advance {$salaryAdvance->reference} - {$salaryAdvance->employee->full_name}",
                    'payee_type' => 'employee',
                    'payee_id' => $salaryAdvance->employee_id,
                    'payee_name' => $salaryAdvance->employee->full_name,
                    'employee_id' => $salaryAdvance->employee_id,
                    'status' => 'approved',
                    'approved' => true,
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                // Create Receipt Item for accounting (Credit Salary Advance Receivable)
                \App\Models\ReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'chart_account_id' => $chartAccounts->salary_advance_receivable_account_id,
                    'amount' => $request->amount,
                    'base_amount' => $request->amount,
                    'vat_amount' => 0,
                    'description' => "Salary advance repayment - {$salaryAdvance->employee->full_name}",
                ]);

                // Create GL Transactions (handled by Receipt model)
                if (method_exists($receipt, 'createGlTransactions')) {
                    $receipt->createGlTransactions();
                }

                return redirect()->route('hr.salary-advances.show', $salaryAdvance)
                    ->with('success', 'Manual repayment recorded successfully.');
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to record repayment: ' . $e->getMessage()])
                ->withInput();
        }
    }

}
