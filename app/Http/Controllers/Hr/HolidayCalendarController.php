<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\HolidayCalendar;
use App\Models\Hr\HolidayCalendarDate;
use App\Models\Hr\PublicHoliday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class HolidayCalendarController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $calendars = HolidayCalendar::where('company_id', current_company_id())
                ->orderBy('calendar_name');

            return DataTables::of($calendars)
                ->addIndexColumn()
                ->addColumn('holidays_count', function ($calendar) {
                    return $calendar->holidays()->count();
                })
                ->addColumn('status_badge', function ($calendar) {
                    $badge = $calendar->is_active ? 'success' : 'secondary';
                    $text = $calendar->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('action', function ($calendar) {
                    $viewBtn = '<a href="' . route('hr.holiday-calendars.show', $calendar->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.holiday-calendars.edit', $calendar->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $calendar->id . '" data-name="' . $calendar->calendar_name . '"><i class="bx bx-trash"></i></button>';
                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.holiday-calendars.index');
    }

    public function create()
    {
        return view('hr-payroll.holiday-calendars.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'calendar_name' => 'required|string|max:200',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $calendar = HolidayCalendar::create([
                'company_id' => current_company_id(),
                'calendar_name' => $validated['calendar_name'],
                'is_active' => $request->has('is_active'),
                'branch_id' => null,
                'country' => null,
                'region' => null,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Holiday calendar created successfully.',
                    'redirect' => route('hr.holiday-calendars.show', $calendar->id)
                ]);
            }

            return redirect()->route('hr.holiday-calendars.show', $calendar->id)
                ->with('success', 'Holiday calendar created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create holiday calendar: ' . $e->getMessage()]);
        }
    }

    public function show(HolidayCalendar $holidayCalendar)
    {
        if ($holidayCalendar->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $holidays = $holidayCalendar->holidays()
            ->orderBy('holiday_date')
            ->get();

        return view('hr-payroll.holiday-calendars.show', compact('holidayCalendar', 'holidays'));
    }

    public function edit(HolidayCalendar $holidayCalendar)
    {
        if ($holidayCalendar->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('hr-payroll.holiday-calendars.edit', compact('holidayCalendar'));
    }

    public function update(Request $request, HolidayCalendar $holidayCalendar)
    {
        if ($holidayCalendar->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'calendar_name' => 'required|string|max:200',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $holidayCalendar->update([
                'calendar_name' => $validated['calendar_name'],
                'is_active' => $request->has('is_active'),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Holiday calendar updated successfully.'
                ]);
            }

            return redirect()->route('hr.holiday-calendars.show', $holidayCalendar->id)
                ->with('success', 'Holiday calendar updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update holiday calendar: ' . $e->getMessage()]);
        }
    }

    public function addHoliday(Request $request, HolidayCalendar $holidayCalendar)
    {
        if ($holidayCalendar->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'holiday_date' => 'required|date',
            'holiday_name' => 'required|string|max:200',
            'holiday_type' => 'required|in:public,company,regional',
            'is_paid' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Check if holiday already exists for this date
        $existing = HolidayCalendarDate::where('calendar_id', $holidayCalendar->id)
            ->where('holiday_date', $validated['holiday_date'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Holiday already exists for this date.'
            ], 400);
        }

        HolidayCalendarDate::create(array_merge($validated, [
            'calendar_id' => $holidayCalendar->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Holiday added successfully.'
        ]);
    }

    public function removeHoliday(HolidayCalendarDate $holidayCalendarDate)
    {
        if ($holidayCalendarDate->calendar->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $holidayCalendarDate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Holiday removed successfully.'
        ]);
    }

    public function destroy(HolidayCalendar $holidayCalendar)
    {
        if ($holidayCalendar->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $holidayCalendar->delete();

        return response()->json([
            'success' => true,
            'message' => 'Holiday calendar deleted successfully.'
        ]);
    }

    /**
     * Seed Tanzania public holidays into a calendar
     */
    public function seedTanzaniaHolidays(Request $request, HolidayCalendar $holidayCalendar)
    {
        if ($holidayCalendar->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'overwrite_existing' => 'boolean',
        ]);

        $year = $validated['year'];
        $overwrite = $request->has('overwrite_existing');

        \Log::info('Starting holiday seeding process', [
            'company_id' => current_company_id(),
            'year' => $year,
            'calendar_id' => $holidayCalendar->id
        ]);

        DB::beginTransaction();
        try {
            // Always ensure holidays are seeded for the requested year (updateOrCreate handles existing records)
            $company = \App\Models\Company::find(current_company_id());
            if (!$company) {
                \Log::error('Company not found for seeding', [
                    'company_id' => current_company_id()
                ]);
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found.'
                ], 404);
            }
            
            \Log::info('Company found, starting seeder', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'year' => $year
            ]);
            
            try {
                $seeder = new \Database\Seeders\TanzaniaPublicHolidaySeeder();
                \Log::info('Seeder instance created, calling run()', [
                    'company_id' => $company->id,
                    'year' => $year
                ]);
                $seeder->run($company, $year);
                \Log::info('Seeder run() completed successfully', [
                    'company_id' => $company->id,
                    'year' => $year
                ]);
            } catch (\Exception $seederException) {
                \Log::error('Seeder failed with exception', [
                    'message' => $seederException->getMessage(),
                    'file' => $seederException->getFile(),
                    'line' => $seederException->getLine(),
                    'trace' => $seederException->getTraceAsString(),
                    'company_id' => current_company_id(),
                    'year' => $year
                ]);
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to seed public holidays: ' . $seederException->getMessage()
                ], 500);
            }
            
            \Log::info('Querying for public holidays after seeding', [
                'company_id' => current_company_id(),
                'year' => $year
            ]);
            
            // Get Tanzania public holidays for the year
            $publicHolidays = PublicHoliday::where('company_id', current_company_id())
                ->whereYear('date', $year)
                ->where('is_active', true)
                ->whereNull('branch_id')
                ->get();
            
            \Log::info('Public holidays query completed', [
                'company_id' => current_company_id(),
                'year' => $year,
                'count' => $publicHolidays->count(),
                'holiday_dates' => $publicHolidays->pluck('date')->toArray()
            ]);
            
            // Also check total holidays for debugging
            $totalHolidays = PublicHoliday::where('company_id', current_company_id())->count();
            $totalForYear = PublicHoliday::where('company_id', current_company_id())
                ->whereYear('date', $year)
                ->count();
            $totalActive = PublicHoliday::where('company_id', current_company_id())
                ->whereYear('date', $year)
                ->where('is_active', true)
                ->count();
            
            \Log::info('Holiday statistics', [
                'total_holidays' => $totalHolidays,
                'total_for_year' => $totalForYear,
                'total_active_for_year' => $totalActive,
                'active_with_null_branch' => $publicHolidays->count()
            ]);
                
            if ($publicHolidays->isEmpty()) {
                \Log::warning('Seeder completed but no holidays found after query', [
                    'company_id' => current_company_id(),
                    'year' => $year,
                    'total_holidays' => $totalHolidays,
                    'total_for_year' => $totalForYear,
                    'total_active' => $totalActive
                ]);
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Seeder ran but no holidays were found for year ' . $year . '. Please check the seeder implementation.'
                ], 500);
            }

            $added = 0;
            $skipped = 0;

            foreach ($publicHolidays as $publicHoliday) {
                // Check if holiday already exists
                $existing = HolidayCalendarDate::where('calendar_id', $holidayCalendar->id)
                    ->where('holiday_date', $publicHoliday->date)
                    ->first();

                if ($existing) {
                    if ($overwrite) {
                        $existing->update([
                            'holiday_name' => $publicHoliday->name,
                            'holiday_type' => 'public',
                            'is_paid' => true,
                            'description' => $publicHoliday->description,
                        ]);
                        $added++;
                    } else {
                        $skipped++;
                    }
                } else {
                    HolidayCalendarDate::create([
                        'calendar_id' => $holidayCalendar->id,
                        'holiday_date' => $publicHoliday->date,
                        'holiday_name' => $publicHoliday->name,
                        'holiday_type' => 'public',
                        'is_paid' => true,
                        'description' => $publicHoliday->description,
                    ]);
                    $added++;
                }
            }

            DB::commit();

            $message = "Successfully added {$added} holiday(s)";
            if ($skipped > 0) {
                $message .= " and skipped {$skipped} existing holiday(s)";
            }
            $message .= " for year {$year}.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'added' => $added,
                'skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to seed holidays: ' . $e->getMessage()
            ], 500);
        }
    }
}

