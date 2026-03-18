<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\PayrollCalendar;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class PayrollCalendarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $calendars = PayrollCalendar::where('company_id', current_company_id())
                ->orderBy('calendar_year', 'desc')
                ->orderBy('payroll_month', 'desc');

            return DataTables::of($calendars)
                ->addIndexColumn()
                ->addColumn('period_label', function ($calendar) {
                    return $calendar->period_label;
                })
                ->addColumn('status_badge', function ($calendar) {
                    if ($calendar->is_locked) {
                        return '<span class="badge bg-danger">Locked</span>';
                    }
                    $today = Carbon::today();
                    if ($today >= $calendar->cut_off_date) {
                        return '<span class="badge bg-warning">Cut-off Passed</span>';
                    }
                    return '<span class="badge bg-success">Active</span>';
                })
                ->addColumn('locked_by_name', function ($calendar) {
                    return $calendar->lockedBy ? $calendar->lockedBy->name : '-';
                })
                ->addColumn('action', function ($calendar) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('hr.payroll-calendars.show', $calendar->id) . '" class="btn btn-sm btn-outline-info"><i class="bx bx-show"></i></a>';
                    
                    if (!$calendar->is_locked) {
                        $actions .= '<a href="' . route('hr.payroll-calendars.edit', $calendar->id) . '" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit"></i></a>';
                        if ($calendar->canBeLocked()) {
                            $actions .= '<button type="button" class="btn btn-sm btn-outline-warning" onclick="lockCalendar(' . $calendar->id . ')"><i class="bx bx-lock"></i></button>';
                        }
                    } else {
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-success" onclick="unlockCalendar(' . $calendar->id . ')" title="Unlock"><i class="bx bx-lock-open"></i></button>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        // Dashboard statistics
        $companyId = current_company_id();
        $today = Carbon::today();
        $thisYear = $today->year;

        $stats = [
            'total' => PayrollCalendar::where('company_id', $companyId)->count(),
            'this_year' => PayrollCalendar::where('company_id', $companyId)
                ->where('calendar_year', $thisYear)
                ->count(),
            'locked' => PayrollCalendar::where('company_id', $companyId)
                ->where('is_locked', true)
                ->count(),
            'upcoming' => PayrollCalendar::where('company_id', $companyId)
                ->where('pay_date', '>=', $today)
                ->where('is_locked', false)
                ->count(),
        ];

        return view('hr-payroll.payroll-calendars.index', compact('stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.payroll-calendars.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'calendar_year' => 'required|integer|min:2020|max:2100',
            'payroll_month' => 'required|integer|min:1|max:12',
            'cut_off_date' => 'required|date',
            'pay_date' => 'required|date|after:cut_off_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check for duplicate
        $exists = PayrollCalendar::where('company_id', current_company_id())
            ->where('calendar_year', $validated['calendar_year'])
            ->where('payroll_month', $validated['payroll_month'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['payroll_month' => 'A payroll calendar already exists for this year and month.']);
        }

        $validated['company_id'] = current_company_id();
        PayrollCalendar::create($validated);

        return redirect()->route('hr.payroll-calendars.index')
            ->with('success', 'Payroll calendar created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PayrollCalendar $payrollCalendar)
    {
        return view('hr-payroll.payroll-calendars.show', compact('payrollCalendar'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PayrollCalendar $payrollCalendar)
    {
        if ($payrollCalendar->is_locked) {
            return redirect()->route('hr.payroll-calendars.index')
                ->with('error', 'Cannot edit a locked payroll calendar.');
        }

        return view('hr-payroll.payroll-calendars.edit', compact('payrollCalendar'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PayrollCalendar $payrollCalendar)
    {
        if ($payrollCalendar->is_locked) {
            return redirect()->route('hr.payroll-calendars.index')
                ->with('error', 'Cannot update a locked payroll calendar.');
        }

        $validated = $request->validate([
            'cut_off_date' => 'required|date',
            'pay_date' => 'required|date|after:cut_off_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payrollCalendar->update($validated);

        return redirect()->route('hr.payroll-calendars.index')
            ->with('success', 'Payroll calendar updated successfully.');
    }

    /**
     * Lock the payroll calendar
     */
    public function lock(PayrollCalendar $payrollCalendar)
    {
        if ($payrollCalendar->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar is already locked.'
            ], 400);
        }

        if (!$payrollCalendar->canBeLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar cannot be locked yet. Cut-off date has not passed.'
            ], 400);
        }

        $payrollCalendar->lock();

        return response()->json([
            'success' => true,
            'message' => 'Payroll calendar locked successfully.'
        ]);
    }

    /**
     * Unlock the payroll calendar
     */
    public function unlock(PayrollCalendar $payrollCalendar)
    {
        if (!$payrollCalendar->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar is not locked.'
            ], 400);
        }

        $payrollCalendar->unlock();

        return response()->json([
            'success' => true,
            'message' => 'Payroll calendar unlocked successfully.'
        ]);
    }
}

