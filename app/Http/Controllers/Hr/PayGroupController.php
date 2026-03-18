<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\PayGroup;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PayGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $payGroups = PayGroup::where('company_id', current_company_id())
                ->orderBy('pay_group_code');

            return DataTables::of($payGroups)
                ->addIndexColumn()
                ->addColumn('employee_count', function ($payGroup) {
                    return $payGroup->employeePayGroups()
                        ->where('effective_date', '<=', now())
                        ->where(function($q) {
                            $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                        })
                        ->count();
                })
                ->addColumn('status_badge', function ($payGroup) {
                    return $payGroup->is_active 
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Inactive</span>';
                })
                ->addColumn('action', function ($payGroup) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('hr.pay-groups.show', $payGroup->id) . '" class="btn btn-sm btn-outline-info"><i class="bx bx-show"></i></a>';
                    $actions .= '<a href="' . route('hr.pay-groups.edit', $payGroup->id) . '" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit"></i></a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        // Dashboard statistics
        $companyId = current_company_id();

        $stats = [
            'total' => PayGroup::where('company_id', $companyId)->count(),
            'active' => PayGroup::where('company_id', $companyId)
                ->where('is_active', true)
                ->count(),
            'monthly' => PayGroup::where('company_id', $companyId)
                ->where('payment_frequency', 'monthly')
                ->count(),
            'daily' => PayGroup::where('company_id', $companyId)
                ->where('payment_frequency', 'daily')
                ->count(),
        ];

        return view('hr-payroll.pay-groups.index', compact('stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.pay-groups.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pay_group_code' => 'required|string|max:50',
            'pay_group_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'payment_frequency' => 'required|in:monthly,daily,weekly,bi-weekly',
            'cut_off_day' => 'nullable|integer|min:1|max:31',
            'pay_day' => 'nullable|integer|min:1|max:31',
            'auto_adjust_weekends' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate code
        $exists = PayGroup::where('company_id', current_company_id())
            ->where('pay_group_code', $validated['pay_group_code'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['pay_group_code' => 'A pay group with this code already exists.']);
        }

        $validated['company_id'] = current_company_id();
        $validated['auto_adjust_weekends'] = $request->has('auto_adjust_weekends');
        $validated['is_active'] = $request->has('is_active');

        PayGroup::create($validated);

        return redirect()->route('hr.pay-groups.index')
            ->with('success', 'Pay group created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PayGroup $payGroup)
    {
        $employees = Employee::where('company_id', current_company_id())
            ->whereHas('payGroupAssignments', function($q) use ($payGroup) {
                $q->where('pay_group_id', $payGroup->id)
                  ->where('effective_date', '<=', now())
                  ->where(function($q2) {
                      $q2->whereNull('end_date')->orWhere('end_date', '>=', now());
                  });
            })
            ->with(['department', 'position'])
            ->get();

        return view('hr-payroll.pay-groups.show', compact('payGroup', 'employees'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PayGroup $payGroup)
    {
        return view('hr-payroll.pay-groups.edit', compact('payGroup'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PayGroup $payGroup)
    {
        $validated = $request->validate([
            'pay_group_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'payment_frequency' => 'required|in:monthly,daily,weekly,bi-weekly',
            'cut_off_day' => 'nullable|integer|min:1|max:31',
            'pay_day' => 'nullable|integer|min:1|max:31',
            'auto_adjust_weekends' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['auto_adjust_weekends'] = $request->has('auto_adjust_weekends');
        $validated['is_active'] = $request->has('is_active');

        $payGroup->update($validated);

        return redirect()->route('hr.pay-groups.index')
            ->with('success', 'Pay group updated successfully.');
    }
}

