<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use App\Models\Loan\Loan;
use App\Models\Loan\LoanDisbursement;
use App\Models\Loan\LoanAccrual;
use App\Models\Loan\LoanRestructureHistory;
use App\Models\Loan\LoanCovenant;
use App\Models\BankAccount;
use App\Models\ChartAccount;
use App\Models\Loan\LoanSchedule;
use App\Models\Loan\LoanPayment;
use App\Models\Loan\LoanFee;
use App\Models\CashDepositAccount;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class LoanController extends Controller
{
    protected $loanService;

    public function __construct(LoanService $loanService)
    {
        $this->loanService = $loanService;
    }
    /**
     * Display a listing of loans
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Get user's assigned branches
        $assignedBranches = $user->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? $user->branch_id);
        $branchId = $request->get('branch_id', $defaultBranchId);
        $status = $request->get('status', 'all');
        
        // Get branches for filter dropdown
        $branches = $assignedBranches;
        if ($assignedBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All My Branches',
                'company_id' => $companyId
            ];
            $branches = $assignedBranches->prepend($allBranchesOption);
        }
        
        // Statistics
        $stats = [
            'total' => Loan::forCompany($companyId)->count(),
            'active' => Loan::forCompany($companyId)->active()->count(),
            'draft' => Loan::forCompany($companyId)->where('status', 'draft')->count(),
            'disbursed' => Loan::forCompany($companyId)->where('status', 'disbursed')->count(),
            'closed' => Loan::forCompany($companyId)->where('status', 'closed')->count(),
        ];
        
        return view('loans.index', compact('branches', 'branchId', 'status', 'stats'));
    }

    /**
     * Get loans data for DataTables
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Get user's assigned branches
        $assignedBranches = $user->branches()->get();
        $branchId = $request->get('branch_id', 'all');
        $status = $request->get('status', 'all');
        
        // Build query
        $query = Loan::forCompany($companyId)
            ->with(['branch', 'bankAccount', 'createdBy']);
        
        // Apply branch filter
        if ($branchId !== 'all') {
            $query->forBranch($branchId);
        } else {
            // If 'all', show loans from all assigned branches
            $assignedBranchIds = $assignedBranches->pluck('id')->toArray();
            if (!empty($assignedBranchIds)) {
                $query->whereIn('branch_id', $assignedBranchIds);
            }
        }
        
        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        // Handle DataTables server-side processing
        return DataTables::eloquent($query)
            ->addColumn('loan_number_link', function($loan) {
                return '<a href="' . route('loans.show', $loan->encoded_id) . '" class="text-primary fw-bold">' . 
                       e($loan->loan_number) . '</a>';
            })
            ->addColumn('bank_name_display', function($loan) {
                return e($loan->bank_name ?? ($loan->bankAccount->name ?? 'N/A'));
            })
            ->addColumn('principal_amount_formatted', function($loan) {
                return number_format($loan->principal_amount, 2) . ' TZS';
            })
            ->addColumn('interest_rate_formatted', function($loan) {
                return number_format($loan->interest_rate, 2) . '%';
            })
            ->addColumn('outstanding_principal_formatted', function($loan) {
                return number_format($loan->outstanding_principal, 2) . ' TZS';
            })
            ->addColumn('status_badge', function($loan) {
                $statusColors = [
                    'draft' => 'secondary',
                    'approved' => 'info',
                    'disbursed' => 'primary',
                    'active' => 'success',
                    'closed' => 'dark',
                    'restructured' => 'warning'
                ];
                $color = $statusColors[$loan->status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst($loan->status) . '</span>';
            })
            ->addColumn('branch_name', function($loan) {
                return e($loan->branch->name ?? 'N/A');
            })
            ->addColumn('actions', function($loan) {
                $html = '<div class="btn-group" role="group">';
                $html .= '<a href="' . route('loans.show', $loan->encoded_id) . '" class="btn btn-sm btn-info" title="View">';
                $html .= '<i class="bx bx-show"></i></a>';
                if($loan->status == 'draft') {
                    $html .= '<a href="' . route('loans.edit', $loan->encoded_id) . '" class="btn btn-sm btn-warning" title="Edit">';
                    $html .= '<i class="bx bx-edit"></i></a>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['loan_number_link', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new loan
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $bankAccounts = BankAccount::with('chartAccount')
            ->orderBy('name')
            ->get();

        $cashDepositAccounts = CashDepositAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->orderBy('name')
            ->get();

        // Get chart accounts filtered by company through account_class_groups
        // Liability accounts (for loan payable)
        $loanLiabilityAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($q2) {
                      $q2->where('name', 'LIKE', '%liabilit%');
                  });
            })
            ->orderBy('account_code')->get();
        
        // Expense accounts (for interest expense and bank charges)
        $interestExpenseAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($q2) {
                      $q2->where('name', 'LIKE', '%expense%');
                  });
            })
            ->orderBy('account_code')->get();
        
        // Liability accounts (for interest payable)
        $interestPayableAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($q2) {
                      $q2->where('name', 'LIKE', '%liabilit%');
                  });
            })
            ->orderBy('account_code')->get();
        
        // Asset accounts (for deferred loan costs)
        $deferredCostAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($q2) {
                      $q2->where('name', 'LIKE', '%asset%');
                  });
            })
            ->orderBy('account_code')->get();
        
        // Expense accounts (for bank charges - same as interest expense)
        $bankChargeAccounts = $interestExpenseAccounts;

        $loanProcessingFeeAccounts = ChartAccount::where('account_code', '5208')
            ->whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->orderBy('account_code')
            ->get();

        return view('loans.create', compact(
            'bankAccounts',
            'cashDepositAccounts',
            'loanLiabilityAccounts',
            'interestExpenseAccounts',
            'interestPayableAccounts',
            'deferredCostAccounts',
            'bankChargeAccounts',
            'loanProcessingFeeAccounts'
        ));
    }

    /**
     * Store a newly created loan
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'cash_deposit_account_id' => 'nullable|exists:cash_deposit_accounts,id',
            'lender_id' => 'nullable|integer',
            'lender_name' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_contact' => 'nullable|string|max:255',
            'facility_name' => 'nullable|string|max:255',
            'facility_type' => 'nullable|in:term_loan,revolving,overdraft,line_of_credit,other',
            'principal_amount' => 'required|numeric|min:0.01',
            'disbursed_amount' => 'nullable|numeric|min:0',
            'disbursement_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'maturity_date' => 'nullable|date',
            'interest_rate' => 'required|numeric|min:0',
            'rate_type' => 'required|in:fixed,variable',
            'base_rate_source' => 'nullable|string|max:255',
            'spread' => 'nullable|numeric|min:0',
            'calculation_basis' => 'required|in:30/360,actual/365,actual/360',
            'payment_frequency' => 'required|in:monthly,quarterly,semi-annual,annual',
            'term_months' => 'required|integer|min:1',
            'first_payment_date' => 'nullable|date',
            'amortization_method' => 'required|in:annuity,straight_principal,interest_only,flat_rate',
            'repayment_method' => 'nullable|in:annuity,equal_principal,interest_only,bullet,flat_rate',
            'grace_period_months' => 'nullable|integer|min:0',
            'fees_amount' => 'nullable|numeric|min:0',
            'capitalise_fees' => 'nullable|boolean',
            'capitalise_interest' => 'nullable|boolean',
            'prepayment_allowed' => 'nullable|boolean',
            'prepayment_penalty_rate' => 'nullable|numeric|min:0|max:100',
            'loan_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'interest_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'interest_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'deferred_loan_costs_account_id' => 'nullable|exists:chart_accounts,id',
            'bank_charges_account_id' => 'nullable|exists:chart_accounts,id',
            'loan_processing_fee_account_id' => 'nullable|exists:chart_accounts,id',
            'capitalised_interest_account_id' => 'nullable|exists:chart_accounts,id',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);

        $loan = new Loan($validated);
        $loan->company_id = $user->company_id;
        $loan->branch_id = session('branch_id') ?? $user->branch_id;
        $loan->status = 'draft';
        
        // Set default values
        if (empty($loan->disbursed_amount)) {
            $loan->disbursed_amount = $loan->principal_amount;
        }
        if (empty($loan->repayment_method)) {
            $loan->repayment_method = $loan->amortization_method;
        }
        if (empty($loan->capitalise_fees)) {
            $loan->capitalise_fees = false;
        }
        if (empty($loan->prepayment_allowed)) {
            $loan->prepayment_allowed = true;
        }
        
        $loan->save();

        return redirect()->route('loans.show', $loan->encoded_id)
            ->with('success', 'Loan created successfully. You can now generate schedule and disburse.');
    }

    /**
     * Display the specified loan
     */
    public function show(string $encodedId)
    {
        $loan = Loan::with([
            'branch', 
            'bankAccount', 
            'loanPayableAccount', 
            'interestExpenseAccount', 
            'interestPayableAccount', 
            'fees', 
            'disbursements' => function($q) {
                $q->orderBy('disb_date', 'desc');
            },
            'accruals' => function($q) {
                $q->orderBy('accrual_date', 'desc');
            },
            'restructureHistory' => function($q) {
                $q->orderBy('restructure_date', 'desc');
            },
            'covenants' => function($q) {
                $q->orderBy('period', 'desc');
            },
            'schedules' => function($q){
                $q->orderBy('installment_no');
            },
            'cashSchedules' => function($q){
                $q->orderBy('installment_no');
            },
            'ifrsSchedules' => function($q){
                $q->orderBy('period_no');
            }, 
            'payments' => function($q){
                $q->orderBy('payment_date', 'desc');
            }
        ])
            ->where('company_id', Auth::user()->company_id)
            ->get()
            ->firstWhere('encoded_id', $encodedId);

        if (!$loan) {
            abort(404);
        }

        // Fix schedules with rounding issues (amount_paid is very close to installment_amount)
        DB::transaction(function () use ($loan) {
            $schedulesFixed = false;
            foreach ($loan->cashSchedules as $schedule) {
                $amountPaid = (float) $schedule->amount_paid;
                $installmentAmount = (float) $schedule->installment_amount;
                
                // Round to 2 decimal places for comparison
                $amountPaidRounded = round($amountPaid, 2);
                $installmentAmountRounded = round($installmentAmount, 2);
                $difference = abs($amountPaidRounded - $installmentAmountRounded);
                
                // If schedule is marked as partial but amount_paid is within 0.02 of installment_amount (tolerance for floating point), fix it
                if ($schedule->status == 'partial' && $difference <= 0.02 && $amountPaid > 0 && $installmentAmount > 0) {
                    $adjustment = $installmentAmountRounded - $amountPaidRounded;
                    
                    // Adjust interest_paid or principal_paid to make up the difference
                    $interestRemaining = max(0.0, (float) $schedule->interest_due - (float) $schedule->interest_paid);
                    if ($interestRemaining > 0 && $adjustment > 0) {
                        $schedule->interest_paid = min((float) $schedule->interest_due, (float) $schedule->interest_paid + $adjustment);
                    } elseif ($adjustment > 0) {
                        $schedule->principal_paid = min((float) $schedule->principal_due, (float) $schedule->principal_paid + $adjustment);
                    }
                    
                    $schedule->amount_paid = $installmentAmountRounded;
                    $schedule->status = 'paid';
                    if (!$schedule->paid_date) {
                        $schedule->paid_date = $schedule->updated_at ?? now();
                    }
                    $schedule->save();
                    $schedulesFixed = true;
                }
            }
            
            // Reload schedules after fixing
            if ($schedulesFixed) {
                $loan->refresh();
                $loan->load('cashSchedules');
            }
        });

        // Simple stats (use cash schedules for display)
        $totalInstallments = $loan->cashSchedules->count();
        $paidInstallments = $loan->cashSchedules->where('status', 'paid')->count();
        $totalInterestScheduled = $loan->cashSchedules->sum('interest_due');
        $nextDue = $loan->cashSchedules->whereIn('status', ['due', 'partial', 'overdue'])->sortBy('due_date')->first();

        // Map of months (Y-m) that already have posted accruals (locked)
        $accruedMonths = $loan->accruals
            ->where('posted_flag', true)
            ->mapWithKeys(function ($accrual) {
                return [$accrual->accrual_date->format('Y-m') => true];
            })
            ->all();

        // Bank accounts for disbursement/payment forms
        $bankAccounts = BankAccount::with('chartAccount')
            ->orderBy('name')
            ->get();

        return view('loans.show', compact(
            'loan',
            'totalInstallments',
            'paidInstallments',
            'totalInterestScheduled',
            'nextDue',
            'bankAccounts',
            'accruedMonths'
        ));
    }

    /**
     * Show the form for editing the specified loan
     */
    public function edit(string $encodedId)
    {
        $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (!$loan) {
            abort(404);
        }

        // Check if loan can be edited (only draft or approved loans can be edited)
        if (!in_array($loan->status, ['draft', 'approved'])) {
            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('error', 'Only draft or approved loans can be edited.');
        }

        // Get bank accounts
        $bankAccounts = BankAccount::with('chartAccount')
            ->orderBy('name')
            ->get();

        // Get chart accounts for GL mapping
        $companyId = Auth::user()->company_id;
        
        // Cash deposit accounts (for bank account selection)
        $cashDepositAccounts = \App\Models\CashDepositAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->orderBy('name')
            ->get();
        
        // Liability accounts (for loan payable)
        $loanLiabilityAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($q2) {
                      $q2->where('name', 'LIKE', '%liabilit%');
                  });
            })
            ->orderBy('account_code')->get();
        
        // Expense accounts (for interest expense)
        $interestExpenseAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($q2) {
                      $q2->where('name', 'LIKE', '%expense%');
                  });
            })
            ->orderBy('account_code')->get();
        
        // Liability accounts (for interest payable)
        $interestPayableAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($q2) {
                      $q2->where('name', 'LIKE', '%liabilit%');
                  });
            })
            ->orderBy('account_code')->get();
        
        // Asset accounts (for deferred loan costs)
        $deferredCostAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($q2) {
                      $q2->where('name', 'LIKE', '%asset%');
                  });
            })
            ->orderBy('account_code')->get();
        
        // Expense accounts (for bank charges - same as interest expense)
        $bankChargeAccounts = $interestExpenseAccounts;

        $loanProcessingFeeAccounts = ChartAccount::where('account_code', '5208')
            ->whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->orderBy('account_code')
            ->get();

        return view('loans.edit', compact(
            'loan',
            'bankAccounts',
            'cashDepositAccounts',
            'loanLiabilityAccounts',
            'interestExpenseAccounts',
            'interestPayableAccounts',
            'deferredCostAccounts',
            'bankChargeAccounts',
            'loanProcessingFeeAccounts'
        ));
    }

    /**
     * Update the specified loan
     */
    public function update(Request $request, string $encodedId)
    {
        $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (!$loan) {
            abort(404);
        }

        // Check if loan can be edited (only draft or approved loans can be edited)
        if (!in_array($loan->status, ['draft', 'approved'])) {
            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('error', 'Only draft or approved loans can be edited.');
        }

        $validated = $request->validate([
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'cash_deposit_account_id' => 'nullable|exists:cash_deposit_accounts,id',
            'lender_id' => 'nullable|integer',
            'lender_name' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_contact' => 'nullable|string|max:255',
            'facility_name' => 'nullable|string|max:255',
            'facility_type' => 'nullable|in:term_loan,revolving,overdraft,line_of_credit,other',
            'principal_amount' => 'required|numeric|min:0.01',
            'disbursed_amount' => 'nullable|numeric|min:0',
            'disbursement_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'maturity_date' => 'nullable|date',
            'interest_rate' => 'required|numeric|min:0',
            'rate_type' => 'required|in:fixed,variable',
            'base_rate_source' => 'nullable|string|max:255',
            'spread' => 'nullable|numeric|min:0',
            'calculation_basis' => 'required|in:30/360,actual/365,actual/360',
            'payment_frequency' => 'required|in:monthly,quarterly,semi-annual,annual',
            'term_months' => 'required|integer|min:1',
            'first_payment_date' => 'nullable|date',
            'amortization_method' => 'required|in:annuity,straight_principal,interest_only,flat_rate',
            'repayment_method' => 'nullable|in:annuity,equal_principal,interest_only,bullet,flat_rate',
            'grace_period_months' => 'nullable|integer|min:0',
            'fees_amount' => 'nullable|numeric|min:0',
            'capitalise_fees' => 'nullable|boolean',
            'capitalise_interest' => 'nullable|boolean',
            'prepayment_allowed' => 'nullable|boolean',
            'prepayment_penalty_rate' => 'nullable|numeric|min:0|max:100',
            'loan_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'interest_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'interest_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'deferred_loan_costs_account_id' => 'nullable|exists:chart_accounts,id',
            'bank_charges_account_id' => 'nullable|exists:chart_accounts,id',
            'loan_processing_fee_account_id' => 'nullable|exists:chart_accounts,id',
            'capitalised_interest_account_id' => 'nullable|exists:chart_accounts,id',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);

        // Update loan with validated data
        $loan->fill($validated);
        
        // Set default values if not provided
        if (empty($loan->disbursed_amount)) {
            $loan->disbursed_amount = $loan->principal_amount;
        }
        if (empty($loan->repayment_method)) {
            $loan->repayment_method = $loan->amortization_method;
        }
        if (empty($loan->capitalise_fees)) {
            $loan->capitalise_fees = false;
        }
        if (empty($loan->prepayment_allowed)) {
            $loan->prepayment_allowed = true;
        }
        
        $loan->save();

        return redirect()->route('loans.show', $loan->encoded_id)
            ->with('success', 'Loan updated successfully.');
    }

    /**
     * Remove the specified loan.
     * Only loans in 'draft' status can be deleted.
     */
    public function destroy(string $encodedId)
    {
        $user = Auth::user();

        // encoded_id is an accessor, not a real column, so we need to
        // fetch the collection and then filter in memory.
        $loan = Loan::forCompany($user->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (! $loan) {
            return redirect()->route('loans.index')->with('error', 'Loan not found.');
        }

        if ($loan->status !== 'draft') {
            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('error', 'Only draft loans can be deleted.');
        }

        try {
            DB::transaction(function () use ($loan) {
                // Delete related records (if any were accidentally created while still draft)
                LoanSchedule::where('loan_id', $loan->id)->delete();
                LoanDisbursement::where('loan_id', $loan->id)->delete();
                LoanAccrual::where('loan_id', $loan->id)->delete();
                LoanPayment::where('loan_id', $loan->id)->delete();
                LoanFee::where('loan_id', $loan->id)->delete();
                LoanRestructureHistory::where('loan_id', $loan->id)->delete();
                LoanCovenant::where('loan_id', $loan->id)->delete();

                // Finally delete the loan itself
                $loan->delete();
            });

            return redirect()->route('loans.index')->with('success', 'Draft loan deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting loan: '.$e->getMessage(), [
                'loan_id' => $loan->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('error', 'Failed to delete loan. Please try again or contact support.');
        }
    }

    /**
     * Disburse a loan
     */
    public function disburse(Request $request, string $encodedId)
    {
        $request->validate([
            'disb_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount_received' => 'required|numeric|min:0',
            'net_proceeds' => 'required|numeric|min:0',
            'bank_charges' => 'nullable|numeric|min:0',
            'ref_number' => 'nullable|string|max:255',
            'narration' => 'nullable|string',
        ]);

        $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (!$loan) {
            return redirect()->back()->with('error', 'Loan not found.');
        }

        if (!in_array($loan->status, ['draft', 'approved'])) {
            return redirect()->route('loans.show', $loan->encoded_id)->with('error', 'Loan already disbursed.');
        }

        try {
            DB::transaction(function () use ($request, $loan) {
                // Create disbursement record
                $disbursement = LoanDisbursement::create([
                    'loan_id' => $loan->id,
                    'disb_date' => Carbon::parse($request->input('disb_date')),
                    'amount_received' => (float) $request->input('amount_received'),
                    'net_proceeds' => (float) $request->input('net_proceeds'),
                    'bank_account_id' => (int) $request->input('bank_account_id'),
                    'ref_number' => $request->input('ref_number'),
                    'bank_charges' => (float) ($request->input('bank_charges') ?? 0),
                    'narration' => $request->input('narration'),
                    'created_by' => Auth::id(),
                ]);

                // Create GL entry using LoanService
                $this->loanService->createDisbursementGlEntry($disbursement, $loan);

                // Update loan fields
                $loan->disbursement_date = $disbursement->disb_date;
                $loan->start_date = $disbursement->disb_date;
                if (!$loan->maturity_date && $loan->term_months) {
                    $loan->maturity_date = $disbursement->disb_date->copy()->addMonths($loan->term_months);
                }
                $loan->disbursed_amount = $disbursement->amount_received;
                $loan->status = 'disbursed';
                $loan->outstanding_principal = $loan->principal_amount;
                $loan->save();
            });

            return redirect()->route('loans.show', $loan->encoded_id)->with('success', 'Loan disbursed and journal posted.');
        } catch (\Exception $e) {
            \Log::error('Loan disbursement error: ' . $e->getMessage(), [
                'loan_id' => $loan->id,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withInput()->with('error', 'Failed to disburse loan: ' . $e->getMessage());
        }
    }

    /**
     * Generate loan schedules (both cash and IFRS)
     */
    public function generateSchedule(Request $request, string $encodedId)
    {
        $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (!$loan) {
            return redirect()->back()->with('error', 'Loan not found.');
        }

        // Prevent regenerating if already paid any installment
        if ($loan->cashSchedules()->where('amount_paid', '>', 0)->exists()) {
            return redirect()->route('loans.show', $loan->encoded_id)->with('error', 'Cannot regenerate schedule after payments have been recorded.');
        }

        try {
            DB::transaction(function () use ($loan) {
                // Clear existing schedules
                $loan->cashSchedules()->delete();
                $loan->ifrsSchedules()->delete();

                // Generate both schedules using LoanService
                $result = $this->loanService->generateDualSchedules($loan);
                
                $cashSchedules = $result['cash_schedules'];
                $ifrsSchedules = $result['ifrs_schedules'];
                
                // Insert cash schedules (contractual)
                $cashScheduleModels = [];
                foreach ($cashSchedules as $schedule) {
                    $cashScheduleModels[] = \App\Models\Loan\LoanCashSchedule::create($schedule);
                }
                
                // Insert IFRS schedules (accounting) and link to cash schedules
                foreach ($ifrsSchedules as $index => $ifrsSchedule) {
                    if (isset($cashScheduleModels[$index])) {
                        $ifrsSchedule['cash_schedule_id'] = $cashScheduleModels[$index]->id;
                    }
                    \App\Models\Loan\LoanIfrsSchedule::create($ifrsSchedule);
                }
                
                // Update loan's current amortised cost from last IFRS schedule
                if (!empty($ifrsSchedules)) {
                    $lastIfrs = end($ifrsSchedules);
                    $loan->current_amortised_cost = $lastIfrs['closing_amortised_cost'];
                    $loan->save();
                }
            });

            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('success', 'Repayment schedules (contractual and IFRS 9) generated successfully.');
        } catch (\Exception $e) {
            \Log::error('Schedule generation error: ' . $e->getMessage(), [
                'loan_id' => $loan->id,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to generate schedules: ' . $e->getMessage());
        }
    }

    /**
     * Show form for creating payment
     */
    public function createPayment(string $encodedId)
    {
            $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
            if (!$loan) {
                abort(404);
            }

            $bankAccounts = BankAccount::with('chartAccount')
                ->orderBy('name')
                ->get();

            return view('loans.payment-create', compact('loan', 'bankAccounts'));
    }

    /**
     * Store payment
     */
    public function storePayment(Request $request, string $encodedId)
    {
        $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (!$loan) {
            return redirect()->back()->with('error', 'Loan not found.');
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'loan_schedule_id' => 'nullable|exists:loan_cash_schedules,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $loan) {
            $amount = (float) $validated['amount'];
            $scheduleId = $validated['loan_schedule_id'] ?? null;

            // Allocate: interest first, then principal
            $remaining = $amount;
            $interestAllocated = 0.0;
            $principalAllocated = 0.0;
            $targetSchedule = null;

            // If specific schedule is selected, start from that schedule
            if ($scheduleId) {
                $targetSchedule = $loan->schedules()->find($scheduleId);
                if (!$targetSchedule) {
                    throw new \Exception('Selected schedule not found.');
                }
                
                // Allocate to the selected schedule first
                $interestDue = max(0.0, (float) $targetSchedule->interest_due - (float) $targetSchedule->interest_paid);
                if ($interestDue > 0 && $remaining > 0) {
                    $alloc = min($remaining, $interestDue);
                    $targetSchedule->interest_paid += $alloc;
                    $targetSchedule->amount_paid += $alloc;
                    $remaining -= $alloc;
                    $interestAllocated += $alloc;
                }
                
                $principalDue = max(0.0, (float) $targetSchedule->principal_due - (float) $targetSchedule->principal_paid);
                if ($principalDue > 0 && $remaining > 0) {
                    $alloc = min($remaining, $principalDue);
                    $targetSchedule->principal_paid += $alloc;
                    $targetSchedule->amount_paid += $alloc;
                    $remaining -= $alloc;
                    $principalAllocated += $alloc;
                    // Reduce closing principal accordingly
                    $targetSchedule->closing_principal = max(0.0, (float) $targetSchedule->closing_principal - $alloc);
                }
                
                // Update schedule status (with tolerance for rounding differences)
                $amountPaid = round((float) $targetSchedule->amount_paid, 2);
                $installmentAmount = round((float) $targetSchedule->installment_amount, 2);
                $difference = abs($amountPaid - $installmentAmount);
                
                // Consider paid if amount_paid >= installment_amount OR difference is less than or equal to 0.01 (rounding tolerance)
                if ($amountPaid >= $installmentAmount || ($difference <= 0.01 && $amountPaid > 0)) {
                    // If there's a small rounding difference, adjust amount_paid to match installment_amount
                    if ($difference <= 0.01 && $amountPaid < $installmentAmount) {
                        $originalAmountPaid = (float) $targetSchedule->amount_paid;
                        $adjustment = $installmentAmount - $originalAmountPaid;
                        $targetSchedule->amount_paid = $installmentAmount;
                        // Adjust interest_paid or principal_paid to make up the difference
                        $interestRemaining = max(0.0, (float) $targetSchedule->interest_due - (float) $targetSchedule->interest_paid);
                        if ($interestRemaining > 0) {
                            $targetSchedule->interest_paid = min((float) $targetSchedule->interest_due, (float) $targetSchedule->interest_paid + $adjustment);
                        } else {
                            $targetSchedule->principal_paid = min((float) $targetSchedule->principal_due, (float) $targetSchedule->principal_paid + $adjustment);
                        }
                    }
                    $targetSchedule->status = 'paid';
                    $targetSchedule->paid_date = $validated['payment_date'];
                } elseif ($targetSchedule->amount_paid > 0) {
                    $targetSchedule->status = 'partial';
                }
                $targetSchedule->save();
            }

            // If there's remaining amount (overpayment) or no schedule selected, allocate to next schedules
            if ($remaining > 0) {
                $rows = $loan->schedules()
                    ->orderBy('due_date')
                    ->orderBy('installment_no')
                    ->get();
                
                foreach ($rows as $row) {
                    if ($remaining <= 0) {
                        break;
                    }
                    
                    // Skip if this is the target schedule (already processed)
                    if ($scheduleId && $row->id == $scheduleId) {
                        continue;
                    }
                    
                    // Skip if already fully paid
                    if ($row->status == 'paid') {
                        continue;
                    }
                    
                    // If no schedule selected, only process due/partial/overdue schedules
                    if (!$scheduleId && !in_array($row->status, ['due', 'partial', 'overdue'])) {
                        continue;
                    }
                    
                    $interestDue = max(0.0, (float) $row->interest_due - (float) $row->interest_paid);
                    if ($interestDue > 0) {
                        $alloc = min($remaining, $interestDue);
                        $row->interest_paid += $alloc;
                        $row->amount_paid += $alloc;
                        $remaining -= $alloc;
                        $interestAllocated += $alloc;
                    }
                    
                    if ($remaining <= 0) {
                        break;
                    }
                    
                    $principalDue = max(0.0, (float) $row->principal_due - (float) $row->principal_paid);
                    if ($principalDue > 0) {
                        $alloc = min($remaining, $principalDue);
                        $row->principal_paid += $alloc;
                        $row->amount_paid += $alloc;
                        $remaining -= $alloc;
                        $principalAllocated += $alloc;
                        // Reduce closing principal accordingly
                        $row->closing_principal = max(0.0, (float) $row->closing_principal - $alloc);
                    }
                    
                    // Update status (with tolerance for rounding differences)
                    $amountPaid = round((float) $row->amount_paid, 2);
                    $installmentAmount = round((float) $row->installment_amount, 2);
                    $difference = abs($amountPaid - $installmentAmount);
                    
                    // Consider paid if amount_paid >= installment_amount OR difference is less than or equal to 0.01 (rounding tolerance)
                    if ($amountPaid >= $installmentAmount || ($difference <= 0.01 && $amountPaid > 0)) {
                        // If there's a small rounding difference, adjust amount_paid to match installment_amount
                        if ($difference <= 0.01 && $amountPaid < $installmentAmount) {
                            $originalAmountPaid = (float) $row->amount_paid;
                            $adjustment = $installmentAmount - $originalAmountPaid;
                            $row->amount_paid = $installmentAmount;
                            // Adjust interest_paid or principal_paid to make up the difference
                            $interestRemaining = max(0.0, (float) $row->interest_due - (float) $row->interest_paid);
                            if ($interestRemaining > 0) {
                                $row->interest_paid = min((float) $row->interest_due, (float) $row->interest_paid + $adjustment);
                            } else {
                                $row->principal_paid = min((float) $row->principal_due, (float) $row->principal_paid + $adjustment);
                            }
                        }
                        $row->status = 'paid';
                        $row->paid_date = $validated['payment_date'];
                    } elseif ($row->amount_paid > 0) {
                        $row->status = 'partial';
                    }
                    $row->save();
                }
            }

            // Update loan totals
            $loan->total_interest_paid += $interestAllocated;
            $loan->total_principal_paid += $principalAllocated;
            $loan->outstanding_principal = max(0.0, (float) $loan->outstanding_principal - $principalAllocated);
            if ($loan->outstanding_principal <= 0.0) {
                $loan->status = 'closed';
            } else {
                $loan->status = 'active';
            }
            $loan->save();

            // Record payment
            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'loan_schedule_id' => $scheduleId,
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'allocation_interest' => $interestAllocated,
                'allocation_principal' => $principalAllocated,
                'allocation_fees' => 0.0,
                'allocation_penalty' => 0.0,
                'bank_account_id' => $validated['bank_account_id'],
                'payment_method' => 'bank',
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Create GL entry using LoanService
            $this->loanService->createPaymentGlEntry($payment, $loan);
        });

        return redirect()->route('loans.show', $loan->encoded_id)->with('success', 'Payment recorded and journal posted.');
    }

    /**
     * Accrue interest for a loan
     */
    public function accrueInterest(Request $request, string $encodedId)
    {
        $request->validate([
            'accrual_date' => 'required|date',
        ]);

        $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (!$loan) {
            return redirect()->back()->with('error', 'Loan not found.');
        }

        $accrualDate = Carbon::parse($request->input('accrual_date'));

        try {
            DB::transaction(function () use ($loan, $accrualDate) {
                // Use schedule-based interest for this accrual month and immediately post to GL
                $accrual = $this->loanService->accrueInterestFromSchedule($loan, $accrualDate);
            $this->loanService->createAccrualGlEntry($accrual, $loan);
        });

            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('success', 'Interest accrued and journal posted.');
        } catch (\Exception $e) {
            \Log::error('Loan interest accrual error: '.$e->getMessage(), [
                'loan_id' => $loan->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Process prepayment
     */
    public function prepayment(Request $request, string $encodedId)
    {
        $request->validate([
            'prepayment_date' => 'required|date',
            'prepayment_amount' => 'required|numeric|min:0.01',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (!$loan) {
            return redirect()->back()->with('error', 'Loan not found.');
        }

        if (!$loan->prepayment_allowed) {
            return redirect()->route('loans.show', $loan->encoded_id)->with('error', 'Prepayment is not allowed for this loan.');
        }

        $prepaymentDate = Carbon::parse($request->input('prepayment_date'));
        $prepaymentAmount = (float) $request->input('prepayment_amount');
        $penalty = $this->loanService->calculatePrepaymentPenalty($loan, $prepaymentAmount, $prepaymentDate);

        DB::transaction(function () use ($request, $loan, $prepaymentDate, $prepaymentAmount, $penalty) {
            // Calculate accrued interest to date
            $accruedInterest = $loan->accrued_interest;
            
            // Allocate payment: interest first, then principal
            $interestAllocated = min($accruedInterest, $prepaymentAmount);
            $principalAllocated = $prepaymentAmount - $interestAllocated - $penalty;
            
            // Create payment record
            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'payment_date' => $prepaymentDate,
                'amount' => $prepaymentAmount + $penalty,
                'allocation_interest' => $interestAllocated,
                'allocation_principal' => $principalAllocated,
                'allocation_fees' => 0,
                'allocation_penalty' => $penalty,
                'bank_account_id' => $request->input('bank_account_id'),
                'payment_method' => 'bank',
                'reference' => $request->input('reference'),
                'payment_ref' => $request->input('reference'),
                'notes' => $request->input('notes') . ' (Prepayment)',
                'created_by' => Auth::id(),
            ]);

            // Update loan balances
            $loan->total_interest_paid += $interestAllocated;
            $loan->total_principal_paid += $principalAllocated;
            $loan->outstanding_principal = max(0, $loan->outstanding_principal - $principalAllocated);
            $loan->accrued_interest = max(0, $loan->accrued_interest - $interestAllocated);
            
            if ($loan->outstanding_principal <= 0) {
                $loan->status = 'closed';
            }
            $loan->save();

            // Create GL entry
            $this->loanService->createPaymentGlEntry($payment, $loan);
        });

        return redirect()->route('loans.show', $loan->encoded_id)->with('success', 'Prepayment processed and journal posted.');
    }

    /**
     * Restructure a loan
     */
    public function restructure(Request $request, string $encodedId)
    {
        $request->validate([
            'restructure_date' => 'required|date',
            'reason' => 'required|string',
            'new_terms_summary' => 'required|string',
            'new_interest_rate' => 'nullable|numeric|min:0',
            'new_term_months' => 'nullable|integer|min:1',
            'new_payment_frequency' => 'nullable|in:monthly,quarterly,semi-annual,annual',
            'approved_by' => 'nullable|exists:users,id',
            'approval_notes' => 'nullable|string',
        ]);

        $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (!$loan) {
            return redirect()->back()->with('error', 'Loan not found.');
        }

        DB::transaction(function () use ($request, $loan) {
            // Store old terms
            $oldTerms = [
                'interest_rate' => $loan->interest_rate,
                'term_months' => $loan->term_months,
                'payment_frequency' => $loan->payment_frequency,
                'amortization_method' => $loan->amortization_method,
            ];

            // Update loan with new terms
            if ($request->has('new_interest_rate')) {
                $loan->interest_rate = $request->input('new_interest_rate');
            }
            if ($request->has('new_term_months')) {
                $loan->term_months = $request->input('new_term_months');
            }
            if ($request->has('new_payment_frequency')) {
                $loan->payment_frequency = $request->input('new_payment_frequency');
            }
            $loan->status = 'restructured';
            $loan->save();

            // Create restructure history record
            LoanRestructureHistory::create([
                'loan_id' => $loan->id,
                'restructure_date' => Carbon::parse($request->input('restructure_date')),
                'reason' => $request->input('reason'),
                'new_terms_summary' => $request->input('new_terms_summary'),
                'old_terms' => $oldTerms,
                'new_terms' => [
                    'interest_rate' => $loan->interest_rate,
                    'term_months' => $loan->term_months,
                    'payment_frequency' => $loan->payment_frequency,
                    'amortization_method' => $loan->amortization_method,
                ],
                'approved_by' => $request->input('approved_by') ?? Auth::id(),
                'approval_notes' => $request->input('approval_notes'),
                'created_by' => Auth::id(),
            ]);
        });

        return redirect()->route('loans.show', $loan->encoded_id)->with('success', 'Loan restructured successfully. You may need to regenerate the schedule.');
    }

    /**
     * Retroactively create GL entries for an existing loan
     */
    public function postToGl(string $encodedId)
    {
        $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (!$loan) {
            return redirect()->back()->with('error', 'Loan not found.');
        }

        try {
            $results = $this->loanService->retroactivelyCreateGlEntries($loan);
            
            $message = "GL entries created successfully. ";
            $message .= "Disbursement: " . ($results['disbursement'] ? 'Yes' : 'No') . ", ";
            $message .= "Accruals: {$results['accruals']}, ";
            $message .= "Payments: {$results['payments']}";
            
            if (!empty($results['errors'])) {
                $message .= ". Errors: " . implode(', ', $results['errors']);
                return redirect()->route('loans.show', $loan->encoded_id)
                    ->with('warning', $message);
            }
            
            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            \Log::error('Loan GL posting error: ' . $e->getMessage(), [
                'loan_id' => $loan->id,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to post loan to GL: ' . $e->getMessage());
        }
    }

    /**
     * Stop capitalising interest (IAS 23) for a loan by setting a capitalisation end date.
     */
    public function stopCapitalisation(Request $request, string $encodedId)
    {
        $request->validate([
            'capitalisation_end_date' => 'required|date',
        ]);

        $loan = Loan::where('company_id', Auth::user()->company_id)->get()->firstWhere('encoded_id', $encodedId);
        if (! $loan) {
            return redirect()->back()->with('error', 'Loan not found.');
        }

        if (! $loan->capitalise_interest || ! $loan->capitalised_interest_account_id) {
            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('error', 'This loan is not set to capitalise interest under IAS 23.');
        }

        if (! in_array($loan->status, ['disbursed', 'active'])) {
            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('error', 'Capitalisation end date can only be set for disbursed or active loans.');
        }

        $endDate = Carbon::parse($request->input('capitalisation_end_date'));

        if ($loan->capitalisation_end_date && $endDate->lt($loan->capitalisation_end_date)) {
            return redirect()->route('loans.show', $loan->encoded_id)
                ->with('error', 'Capitalisation end date cannot be earlier than the existing end date.');
        }

        $loan->capitalisation_end_date = $endDate;
        // Keep capitalise_interest flag true for history; logic uses end date to switch to expense.
        $loan->save();

        return redirect()->route('loans.show', $loan->encoded_id)
            ->with('success', 'Capitalisation end date set successfully. Future interest will be expensed.');
    }

    /**
     * Export loan to PDF
     */
    public function exportPdf(string $id)
    {
        //
    }

    /**
     * Export loan to Excel
     */
    public function exportExcel(string $id)
    {
        //
    }
}
