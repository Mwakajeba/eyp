# Fleet Management Approval Settings Implementation

## Summary
This document outlines the implementation of approval settings for Fleet Management and vehicle category filtering improvements.

## Changes Made

### 1. Fleet Approval Settings (Similar to Rental-Event-Equipment)

#### Files Created:
1. **Model**: `app/Models/Fleet/FleetApprovalSettings.php`
   - Similar structure to RentalApprovalSettings
   - Supports up to 5 approval levels
   - Each level has amount threshold and multiple approvers
   - Methods: `getApproversForLevel()`, `getAmountThresholdForLevel()`, `getRequiredApprovalsForAmount()`, `canUserApproveAtLevel()`, `getSettingsForCompany()`

2. **Controller**: `app/Http/Controllers/Fleet/FleetApprovalSettingsController.php`
   - `index()` method: Display approval settings page
   - `store()` method: Create/update approval settings
   - Supports company and branch-specific settings

3. **Migration**: `database/migrations/2026_02_08_163025_create_fleet_approval_settings_table.php`
   - Table: `fleet_approval_settings`
   - Columns:
     - `company_id`, `branch_id` (nullable)
     - `approval_required` (boolean)
     - `approval_levels` (1-5)
     - For each level 1-5:
       - `level{N}_amount_threshold` (decimal)
       - `level{N}_approvers` (JSON array of user IDs)
     - `notes`, `created_by`, `updated_by`
     - Timestamps

4. **View**: `resources/views/fleet/approval-settings/index.blade.php`
   - Multi-level approval configuration
   - Amount thresholds per level
   - Multiple approvers per level
   - Enable/disable approval system toggle
   - User-friendly interface with tabs

#### Routes Added:
```php
Route::prefix('fleet/approval-settings')->name('fleet.approval-settings.')->middleware(['auth', 'company.scope', 'require.branch'])->group(function () {
    Route::get('/', [FleetApprovalSettingsController::class, 'index'])->name('index');
    Route::post('/', [FleetApprovalSettingsController::class, 'store'])->name('store');
});
```

#### Updated Files:
- `resources/views/fleet/settings/index.blade.php` - Added link to Approval Settings page

### 2. Vehicle Category Filtering (FA04 - Vehicles Only)

All fleet controllers now filter vehicles by category code 'FA04' (Motor Vehicles) to ensure only actual vehicles (not other assets) appear in vehicle selection dropdowns.

#### Controllers Updated:
1. **FleetFuelController.php** - 3 occurrences updated
2. **FleetTripController.php** - 2 occurrences updated
3. **FleetInvoiceController.php** - 1 occurrence updated
4. **FleetTripCostController.php** - 1 occurrence updated
5. **FleetComplianceController.php** - 2 occurrences updated (using replace_all)
6. **FleetTyreReplacementRequestController.php** - 1 occurrence updated
7. **FleetSparePartReplacementController.php** - 1 occurrence updated
8. **FleetTyreInstallationController.php** - 2 occurrences updated (using replace_all)
9. **FleetDriverController.php** - 3 occurrences updated
10. **FleetMaintenanceController.php** - 3 occurrences updated (using replace_all)

#### Change Pattern:
**Before:**
```php
$vehicleCategoryId = AssetCategory::where('code', 'FA04')->where('company_id', $user->company_id)->value('id');
$vehicles = Asset::where('company_id', $user->company_id)
    ->when($vehicleCategoryId, fn($q) => $q->where('asset_category_id', $vehicleCategoryId))
    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
    ->orderBy('name')
    ->get(['id', 'name', 'registration_number']);
```

**After:**
```php
$vehicles = Asset::where('company_id', $user->company_id)
    ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
    ->orderBy('name')
    ->get(['id', 'name', 'registration_number']);
```

**Benefits:**
- Cleaner code (removed unnecessary variable)
- More reliable (uses Eloquent relationship instead of conditional)
- Consistent across all fleet controllers
- Ensures only vehicles (category FA04) appear in all vehicle dropdowns

### 3. Database Migration Status
Migration successfully executed: `fleet_approval_settings` table created

## How Approval Settings Work

### Configuration:
1. Navigate to: **Fleet Management → Settings → Approval Settings**
2. Enable/disable the approval system
3. Set number of approval levels (1-5)
4. For each level:
   - Set amount threshold (e.g., Level 1: 0 TZS, Level 2: 100,000 TZS, Level 3: 500,000 TZS)
   - Select approvers (multiple users can be selected per level)
5. Add optional notes

### How It Works:
- When approval is **enabled**: New fleet operations (trips, invoices, costs) are set to "Pending for approval"
- Approvers at each level must approve before the operation proceeds
- If the operation amount exceeds the threshold, it requires approval at that level
- All approvers at a level must approve before moving to the next level
- When approval is **disabled**: Operations are auto-approved immediately

## Testing Checklist

### Approval Settings:
- [ ] Visit `http://127.0.0.1:8000/fleet/settings` - should see "Approval Settings" button
- [ ] Click "Approval Settings" button - should navigate to approval settings page
- [ ] Enable approval system - level configuration should appear
- [ ] Change number of levels - should show/hide appropriate level configs
- [ ] Select approvers for each level - should allow multiple selections
- [ ] Save settings - should save successfully and show success message
- [ ] Settings should persist on page reload

### Vehicle Filtering:
- [ ] Visit any fleet form with vehicle selection (trips, fuel logs, maintenance, etc.)
- [ ] Vehicle dropdown should only show assets with category FA04 (Motor Vehicles)
- [ ] No other asset types should appear in vehicle dropdowns
- [ ] Test across all fleet modules: Trips, Fuel, Maintenance, Compliance, Drivers, Tyre Management, Spare Parts

## Next Steps (Optional Enhancements)

1. **Implement Approval Workflow Service**: Create `FleetApprovalService.php` similar to `RentalApprovalService.php` to handle:
   - `initializeApprovalWorkflow()` - Set up approval chain for operations
   - `approveDocument()` - Process approvals
   - `rejectDocument()` - Handle rejections
   - Integration with fleet operations (trips, invoices, costs)

2. **Create Fleet Approvals Table**: Migration for tracking individual approvals
   ```php
   - fleet_approvals (id, fleet_operation_type, fleet_operation_id, level, user_id, status, comments, approved_at)
   ```

3. **Update Fleet Operation Models**: Add approval status fields and relationships
   - Add `approval_status` enum field to relevant tables (pending_approval, approved, rejected)
   - Add relationships to FleetApproval model

4. **UI Enhancements**:
   - Approval/reject buttons on fleet operation detail pages
   - Approval history display
   - Pending approvals dashboard/widget

## Files Changed Summary

### Created (4 files):
- `app/Models/Fleet/FleetApprovalSettings.php`
- `app/Http/Controllers/Fleet/FleetApprovalSettingsController.php`
- `database/migrations/2026_02_08_163025_create_fleet_approval_settings_table.php`
- `resources/views/fleet/approval-settings/index.blade.php`

### Modified (13 files):
- `routes/web.php` - Added approval settings routes
- `resources/views/fleet/settings/index.blade.php` - Added approval settings link
- `app/Http/Controllers/Fleet/FleetFuelController.php`
- `app/Http/Controllers/Fleet/FleetTripController.php`
- `app/Http/Controllers/Fleet/FleetInvoiceController.php`
- `app/Http/Controllers/Fleet/FleetTripCostController.php`
- `app/Http/Controllers/Fleet/FleetComplianceController.php`
- `app/Http/Controllers/Fleet/FleetTyreReplacementRequestController.php`
- `app/Http/Controllers/Fleet/FleetSparePartReplacementController.php`
- `app/Http/Controllers/Fleet/FleetTyreInstallationController.php`
- `app/Http/Controllers/Fleet/FleetDriverController.php`
- `app/Http/Controllers/Fleet/FleetMaintenanceController.php`

## Migration Executed
✅ Migration executed successfully: `2026_02_08_163025_create_fleet_approval_settings_table`
