<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetComplianceRecord;
use App\Models\Fleet\FleetDriver;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class FleetComplianceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Calculate dashboard statistics
        $complianceQuery = FleetComplianceRecord::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $totalRecords = $complianceQuery->count();
        $activeRecords = (clone $complianceQuery)->where('status', 'active')->count();
        $expiringRecords = (clone $complianceQuery)->where('status', 'expiring_soon')->count();
        $expiredRecords = (clone $complianceQuery)->where('status', 'expired')->count();

        return view('fleet.compliance.index', compact('totalRecords', 'activeRecords', 'expiringRecords', 'expiredRecords'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = FleetComplianceRecord::query()
            ->where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'driver']);

        if ($request->filled('compliance_type')) {
            $query->where('compliance_type', $request->compliance_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('compliance_status')) {
            $query->where('compliance_status', $request->compliance_status);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        return DataTables::of($query)
            ->addColumn('entity_display', function($record) {
                if ($record->vehicle) {
                    return '<span class="badge bg-primary">Vehicle</span> ' . $record->vehicle->name;
                }
                if ($record->driver) {
                    return '<span class="badge bg-success">Driver</span> ' . $record->driver->full_name;
                }
                return '<span class="text-muted">N/A</span>';
            })
            ->addColumn('type_display', function($record) {
                $types = [
                    'vehicle_insurance' => 'Vehicle Insurance',
                    'driver_license' => 'Driver License',
                    'vehicle_inspection' => 'Vehicle Inspection',
                    'safety_certification' => 'Safety Certification',
                    'registration' => 'Registration',
                    'permit' => 'Permit',
                    'other' => 'Other',
                ];
                return $types[$record->compliance_type] ?? $record->compliance_type;
            })
            ->addColumn('status_display', function($record) {
                $color = $record->getStatusColor();
                return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $record->status)) . '</span>';
            })
            ->addColumn('compliance_status_display', function($record) {
                $color = $record->getComplianceStatusColor();
                return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $record->compliance_status)) . '</span>';
            })
            ->addColumn('expiry_display', function($record) {
                $daysUntilExpiry = $record->daysUntilExpiry();
                $expiryDate = $record->expiry_date->format('Y-m-d');
                
                if ($record->isExpired()) {
                    return '<span class="text-danger">' . $expiryDate . '</span><br><small class="text-danger">Expired ' . abs($daysUntilExpiry) . ' days ago</small>';
                } elseif ($record->isExpiringSoon(30)) {
                    return '<span class="text-warning">' . $expiryDate . '</span><br><small class="text-warning">Expires in ' . $daysUntilExpiry . ' days</small>';
                }
                return $expiryDate . '<br><small class="text-muted">' . $daysUntilExpiry . ' days remaining</small>';
            })
            ->addColumn('actions', function($record) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('fleet.compliance.show', $record->hash_id) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
                $actions .= '<a href="' . route('fleet.compliance.edit', $record->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['entity_display', 'type_display', 'status_display', 'compliance_status_display', 'expiry_display', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        $drivers = FleetDriver::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'license_number']);

        $complianceTypes = [
            'vehicle_insurance' => 'Vehicle Insurance',
            'driver_license' => 'Driver License',
            'vehicle_inspection' => 'Vehicle Inspection',
            'safety_certification' => 'Safety Certification',
            'registration' => 'Registration',
            'permit' => 'Permit',
            'other' => 'Other',
        ];

        return view('fleet.compliance.create', compact('vehicles', 'drivers', 'complianceTypes'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'compliance_type' => 'required|in:vehicle_insurance,driver_license,vehicle_inspection,safety_certification,registration,permit,other',
            'vehicle_id' => 'nullable|required_without:driver_id|exists:assets,id',
            'driver_id' => 'nullable|required_without:vehicle_id|exists:fleet_drivers,id',
            'document_number' => 'nullable|string|max:100',
            'issuer_name' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'required|date|after:issue_date',
            'renewal_reminder_date' => 'nullable|date|before:expiry_date',
            'status' => 'required|in:active,expired,pending_renewal,renewed,cancelled',
            'compliance_status' => 'required|in:compliant,non_compliant,warning,critical',
            'premium_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'payment_frequency' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'auto_renewal_enabled' => 'boolean',
            'parent_record_id' => 'nullable|exists:fleet_compliance_records,id',
        ]);

        // Handle file uploads
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('fleet-compliance-attachments', 'public');
                $attachmentPaths[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        }

        // Generate record number
        $recordNumber = FleetComplianceRecord::generateRecordNumber('COMP');

        // Auto-calculate compliance status if not provided
        $complianceStatus = $validated['compliance_status'] ?? 'compliant';
        if ($validated['expiry_date'] < now()) {
            $complianceStatus = 'non_compliant';
            $validated['status'] = 'expired';
        } elseif ($validated['expiry_date'] <= now()->addDays(30)) {
            $complianceStatus = $complianceStatus === 'compliant' ? 'warning' : $complianceStatus;
        }

        $record = FleetComplianceRecord::create(array_merge($validated, [
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'record_number' => $recordNumber,
            'compliance_status' => $complianceStatus,
            'currency' => $validated['currency'] ?? 'TZS',
            'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
            'created_by' => $user->id,
        ]));

        return redirect()->route('fleet.compliance.index')->with('success', 'Compliance record created successfully.');
    }

    public function show(FleetComplianceRecord $record)
    {
        $user = Auth::user();

        if ($record->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $record->load(['vehicle', 'driver', 'parentRecord', 'renewalRecords', 'createdBy', 'updatedBy']);

        return view('fleet.compliance.show', compact('record'));
    }

    public function edit(FleetComplianceRecord $record)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        if ($record->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        $drivers = FleetDriver::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'license_number']);

        $complianceTypes = [
            'vehicle_insurance' => 'Vehicle Insurance',
            'driver_license' => 'Driver License',
            'vehicle_inspection' => 'Vehicle Inspection',
            'safety_certification' => 'Safety Certification',
            'registration' => 'Registration',
            'permit' => 'Permit',
            'other' => 'Other',
        ];

        $parentRecordsQuery = FleetComplianceRecord::where('company_id', $user->company_id)
            ->where('id', '!=', $record->id)
            ->where('compliance_type', $record->compliance_type);
        
        if ($record->vehicle_id) {
            $parentRecordsQuery->where('vehicle_id', $record->vehicle_id);
        } elseif ($record->driver_id) {
            $parentRecordsQuery->where('driver_id', $record->driver_id);
        }
        
        $parentRecords = $parentRecordsQuery->orderBy('expiry_date', 'desc')
            ->get(['id', 'record_number', 'expiry_date']);

        return view('fleet.compliance.edit', compact('record', 'vehicles', 'drivers', 'complianceTypes', 'parentRecords'));
    }

    public function update(Request $request, FleetComplianceRecord $record)
    {
        $user = Auth::user();

        if ($record->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'compliance_type' => 'required|in:vehicle_insurance,driver_license,vehicle_inspection,safety_certification,registration,permit,other',
            'vehicle_id' => 'nullable|required_without:driver_id|exists:assets,id',
            'driver_id' => 'nullable|required_without:vehicle_id|exists:fleet_drivers,id',
            'document_number' => 'nullable|string|max:100',
            'issuer_name' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'required|date|after:issue_date',
            'renewal_reminder_date' => 'nullable|date|before:expiry_date',
            'status' => 'required|in:active,expired,pending_renewal,renewed,cancelled',
            'compliance_status' => 'required|in:compliant,non_compliant,warning,critical',
            'premium_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'payment_frequency' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'auto_renewal_enabled' => 'boolean',
            'parent_record_id' => 'nullable|exists:fleet_compliance_records,id',
            'remove_attachments' => 'nullable|array',
        ]);

        // Handle new file uploads
        $existingAttachments = $record->attachments ?? [];
        $attachmentsToRemove = $request->input('remove_attachments', []);
        
        // Remove specified attachments
        if (!empty($attachmentsToRemove)) {
            foreach ($attachmentsToRemove as $index) {
                if (isset($existingAttachments[$index])) {
                    Storage::disk('public')->delete($existingAttachments[$index]['path'] ?? '');
                    unset($existingAttachments[$index]);
                }
            }
            $existingAttachments = array_values($existingAttachments); // Re-index
        }

        // Add new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('fleet-compliance-attachments', 'public');
                $existingAttachments[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        }

        // Auto-calculate compliance status
        $complianceStatus = $validated['compliance_status'];
        if ($validated['expiry_date'] < now() && $validated['status'] !== 'renewed') {
            $complianceStatus = 'non_compliant';
            $validated['status'] = 'expired';
        } elseif ($validated['expiry_date'] <= now()->addDays(30) && $complianceStatus === 'compliant') {
            $complianceStatus = 'warning';
        }

        $record->update(array_merge($validated, [
            'compliance_status' => $complianceStatus,
            'attachments' => !empty($existingAttachments) ? $existingAttachments : null,
            'updated_by' => $user->id,
        ]));

        return redirect()->route('fleet.compliance.show', $record->hash_id)->with('success', 'Compliance record updated successfully.');
    }

    public function destroy(FleetComplianceRecord $record)
    {
        $user = Auth::user();

        if ($record->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        // Delete attachments
        if ($record->attachments) {
            foreach ($record->attachments as $attachment) {
                Storage::disk('public')->delete($attachment['path'] ?? '');
            }
        }

        $record->delete();

        return redirect()->route('fleet.compliance.index')->with('success', 'Compliance record deleted successfully.');
    }
}
