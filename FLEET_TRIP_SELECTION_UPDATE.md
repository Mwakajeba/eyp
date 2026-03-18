# Fleet Trip Selection Update - Show Date & Exclude Completed Trips

## Summary
Updated all Fleet Management trip selection dropdowns to:
1. Display trip number with date (e.g., "TRIP-001 (08-Feb-2026) - Vehicle Name")
2. Exclude completed trips from selection (prevent assigning costs/fuel to already completed trips)

## Changes Made

### 1. Fleet Trip Cost Controller & View
**File:** `app/Http/Controllers/Fleet/FleetTripCostController.php`
- Changed trip query from `whereIn('status', ['planned', 'dispatched', 'in_progress'])` to `whereNotIn('status', ['completed'])`
- Added `planned_start_date` and `actual_start_date` to select fields
- Trips now include date information for display

**File:** `resources/views/fleet/trip-costs/create.blade.php`
- Updated JavaScript to include `date` property in trips array
- Modified trip option display to show: `${t.trip_number}${t.date ? ' (' + t.date + ')' : ''} - ${t.vehicle_name}`
- Date format: `d-M-Y` (e.g., 08-Feb-2026)

### 2. Fleet Fuel Controller & Views
**File:** `app/Http/Controllers/Fleet/FleetFuelController.php`
- Updated two instances of trip queries:
  - Line ~107: Changed from `whereIn('status', ['dispatched', 'in_progress'])` to `whereNotIn('status', ['completed'])`
  - Line ~465: Added `whereNotIn('status', ['completed'])`

**File:** `resources/views/fleet/fuel/create.blade.php`
- Added PHP code to calculate trip date (actual_start_date ?? planned_start_date)
- Updated option display to show: `{{ $t->trip_number }}@if($dateStr) ({{ $dateStr }})@endif - {{ $t->vehicle->name ?? 'N/A' }}`

**File:** `resources/views/fleet/fuel/edit.blade.php`
- Same changes as create.blade.php
- Maintains pre-selected trip value with date display

### 3. Fleet Invoice Controller
**File:** `app/Http/Controllers/Fleet/FleetInvoiceController.php`

**create() method (Line ~221):**
- Changed from `whereNotIn('status', ['completed'])` (already correct)
- Already had date formatting in place: `$trip->formatted_date`
- Date format: `d/m/Y`

**edit() method (Line ~967):**
- Changed from `whereIn('status', ['dispatched', 'in_progress', 'completed'])` to `whereNotIn('status', ['completed'])`
- Already had date formatting: `$trip->formatted_date`

**Note:** Invoice views already had the date display implemented:
- `resources/views/fleet/invoices/create.blade.php` - Line 239: Shows `${tripDisplay}${dateDisplay} - ${vehicleName}`
- `resources/views/fleet/invoices/edit.blade.php` - Line 123: Shows `{{ $trip->trip_number }}{{ $trip->formatted_date ? ' (' . $trip->formatted_date . ')' : '' }}`

### 4. Date Format Consistency
Two date formats used across the system:
- **d-M-Y** (e.g., 08-Feb-2026): Used in Trip Costs and Fuel modules
- **d/m/Y** (e.g., 08/02/2026): Used in Invoice module (pre-existing format)

## Why Exclude Completed Trips?

Completed trips should not appear in selection dropdowns for:
1. **Trip Costs** - Prevents assigning new costs to already completed/closed trips
2. **Fuel Logs** - Completed trips shouldn't receive new fuel entries
3. **Invoices** - Completed trips may already have invoices; prevents duplicate billing

**Exception:** The invoice index page filter dropdown still shows all trips (including completed) to allow filtering/viewing invoices for completed trips.

## Date Priority
When displaying trip dates, the system prioritizes:
1. **actual_start_date** (if the trip has actually started)
2. **planned_start_date** (if trip hasn't started yet)
3. **created_at** (fallback - invoice module only)

## Testing Checklist

### Trip Costs
- [ ] Visit `http://127.0.0.1:8000/fleet/trip-costs/create`
- [ ] Verify trip dropdown shows: "TRIP-XXX (DD-MMM-YYYY) - Vehicle Name"
- [ ] Verify completed trips are not in the dropdown
- [ ] Add multiple trip lines and verify each shows the date

### Fuel Logs
- [ ] Visit `http://127.0.0.1:8000/fleet/fuel/create`
- [ ] Verify trip dropdown shows: "TRIP-XXX (DD-MMM-YYYY) - Vehicle Name"
- [ ] Verify completed trips are not in the dropdown
- [ ] Edit existing fuel log - verify trip dropdown shows dates

### Invoices
- [ ] Visit `http://127.0.0.1:8000/fleet/invoices/create`
- [ ] Verify trip dropdown in invoice items shows: "TRIP-XXX (DD/MM/YYYY) - Vehicle Name"
- [ ] Verify completed trips are not in the dropdown
- [ ] Edit existing invoice - verify same format
- [ ] Index page filter - verify all trips (including completed) appear for filtering

## Files Modified (6 files)

### Controllers (2 files):
1. `app/Http/Controllers/Fleet/FleetTripCostController.php`
2. `app/Http/Controllers/Fleet/FleetFuelController.php`
3. `app/Http/Controllers/Fleet/FleetInvoiceController.php`

### Views (4 files):
1. `resources/views/fleet/trip-costs/create.blade.php`
2. `resources/views/fleet/fuel/create.blade.php`
3. `resources/views/fleet/fuel/edit.blade.php`
4. (Invoice views already had date display implemented)

## Benefits

1. **Better Trip Identification**: Users can quickly identify trips by both number and date
2. **Data Integrity**: Prevents assigning costs/fuel to completed trips
3. **Improved UX**: More context in dropdowns reduces selection errors
4. **Consistency**: Standardized trip display format across Fleet Management

## Future Enhancements (Optional)

- Standardize date format across all modules (choose either d-M-Y or d/m/Y)
- Add trip status indicator in dropdown (e.g., color coding or icon)
- Include customer name in trip display for customer-based trips
- Add trip route information in dropdown tooltip
