<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\InventoryLocation;
use App\Models\RentalEventEquipment\RentalInventorySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RentalInventorySettingController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $settings = RentalInventorySetting::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->first();

        if (! $settings) {
            $settings = new RentalInventorySetting([
                'company_id' => $companyId,
                'branch_id' => $branchId,
            ]);
        }

        $locations = InventoryLocation::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        return view('rental-event-equipment.rental-inventory-settings.index', compact('settings', 'locations'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $request->validate([
            'default_storage_location_id' => 'nullable|exists:inventory_locations,id',
            'out_on_rent_location_id' => 'nullable|exists:inventory_locations,id',
        ]);

        RentalInventorySetting::updateOrCreate(
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
            ],
            [
                'default_storage_location_id' => $request->default_storage_location_id,
                'out_on_rent_location_id' => $request->out_on_rent_location_id,
            ]
        );

        return redirect()->route('rental-event-equipment.rental-inventory-settings.index')
            ->with('success', 'Rental inventory settings saved. Dispatch/return will update stock when equipment is linked to inventory items.');
    }
}
