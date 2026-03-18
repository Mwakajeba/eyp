<?php

namespace App\Http\Controllers;

use App\Models\StoreRequisition;
use App\Models\StoreRequisitionApproval;
use App\Models\StoreRequisitionApprovalSettings;
use App\Models\StoreRequisitionReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreRequisitionActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Approve or reject a store requisition
     */
    public function approve(Request $request, $id)
    {
        try {
            Log::info("Store requisition approval request received", [
                'id' => $id,
                'action' => $request->get('action'),
                'user_id' => Auth::id()
            ]);
            
            $requisition = $this->resolveStoreRequisition($id);
            
            Log::info("Store requisition resolved", [
                'requisition_id' => $requisition->id,
                'status' => $requisition->status,
                'level' => $requisition->current_approval_level
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Store requisition not found", ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Store requisition not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error in approve method", [
                'id' => $id,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
        
        // Validate user can approve this requisition
        if (!$this->canUserApprove($requisition, Auth::user())) {
            Log::warning("User cannot approve store requisition", [
                'user_id' => Auth::id(),
                'requisition_id' => $requisition->id,
                'current_level' => $requisition->current_approval_level,
                'requisition_status' => $requisition->status
            ]);
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve this store requisition.'
            ], 403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:1000'
        ]);

        // Additional validation for approval with items
        if ($request->action === 'approve') {
            $request->validate([
                'items' => 'nullable|array',
                'items.*.quantity_approved' => 'nullable|numeric|min:0'
            ]);
        }

        // Additional validation for rejection
        if ($request->action === 'reject') {
            $request->validate([
                'comments' => 'required|string|min:5|max:1000'
            ], [
                'comments.required' => 'Comments are required when rejecting a store requisition.',
                'comments.min' => 'Rejection reason must be at least 5 characters.'
            ]);
        }

        try {
            DB::beginTransaction();

            $currentLevel = $requisition->current_approval_level;
            $approvalSettings = StoreRequisitionApprovalSettings::where('company_id', $requisition->company_id)->first();
            $nextLevel = null;

            // Map frontend action to database enum value
            $actionValue = $request->action === 'approve' ? 'approved' : 'rejected';

            // Record approval action
            StoreRequisitionApproval::create([
                'store_requisition_id' => $requisition->id,
                'approval_level' => $currentLevel,
                'approver_id' => Auth::id(),
                'action' => $actionValue,
                'action_date' => now(),
                'comments' => $request->comments,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if ($request->action === 'approve') {
                // Update approved quantities for items if provided
                if ($request->has('items') && is_array($request->items)) {
                    foreach ($request->items as $itemId => $itemData) {
                        if (isset($itemData['quantity_approved'])) {
                            \App\Models\StoreRequisitionItem::where('id', $itemId)
                                ->where('store_requisition_id', $requisition->id)
                                ->update([
                                    'quantity_approved' => $itemData['quantity_approved']
                                ]);
                        }
                    }
                }

                // Check if this is the final approval level
                $nextLevel = $this->getNextApprovalLevel($approvalSettings, $currentLevel);
                
                if ($nextLevel) {
                    // Move to next approval level - keep status as pending
                    $requisition->update([
                        'status' => 'pending',
                        'current_approval_level' => $nextLevel,
                        'current_approver_id' => $this->getApproverForLevel($approvalSettings, $nextLevel),
                    ]);
                    $message = "Store requisition approved at Level {$currentLevel} and forwarded to Level {$nextLevel}.";
                    
                    // Log partial approval
                    if (method_exists($requisition, 'logActivity')) {
                        $requisition->logActivity('approve', "Partially Approved Store Requisition {$requisition->requisition_number} at Level {$currentLevel}", [
                            'Requisition Number' => $requisition->requisition_number,
                            'Approval Level' => $currentLevel,
                            'Next Level' => $nextLevel,
                            'Requisition Date' => $requisition->requisition_date ? $requisition->requisition_date->format('Y-m-d') : 'N/A',
                            'Status' => ucfirst(str_replace('_', ' ', $requisition->status)),
                            'Approved By' => Auth::user()->name
                        ]);
                    }
                } else {
                    // Final approval - mark as approved and ready for issue
                    $requisition->update([
                        'status' => 'approved',
                        'current_approval_level' => $currentLevel,
                        'approved_at' => now(),
                        'approved_by' => Auth::id(),
                        'current_approver_id' => null,
                    ]);
                    $message = 'Store requisition fully approved and ready for store issue.';
                    
                    // Log final approval
                    if (method_exists($requisition, 'logActivity')) {
                        $requisition->logActivity('approve', "Fully Approved Store Requisition {$requisition->requisition_number} at Final Level {$currentLevel}", [
                            'Requisition Number' => $requisition->requisition_number,
                            'Final Approval Level' => $currentLevel,
                            'Requisition Date' => $requisition->requisition_date ? $requisition->requisition_date->format('Y-m-d') : 'N/A',
                            'Status' => 'Approved',
                            'Approved By' => Auth::user()->name,
                            'Approved At' => now()->format('Y-m-d H:i:s')
                        ]);
                    }
                }

                Log::info("Store requisition {$requisition->requisition_number} approved at level {$currentLevel} by user " . Auth::id());

            } else { // reject
                $requisition->update([
                    'status' => 'rejected',
                    'current_approval_level' => $currentLevel,
                    'rejected_at' => now(),
                    'rejected_by' => Auth::id(),
                    'rejection_reason' => $request->comments,
                    'current_approver_id' => null,
                ]);

                $message = 'Store requisition rejected. Requisitioner can resubmit with corrections.';
                Log::info("Store requisition {$requisition->requisition_number} rejected at level {$currentLevel} by user " . Auth::id());
                
                // Log rejection
                if (method_exists($requisition, 'logActivity')) {
                    $requisition->logActivity('reject', "Rejected Store Requisition {$requisition->requisition_number} at Level {$currentLevel}", [
                        'Requisition Number' => $requisition->requisition_number,
                        'Rejection Level' => $currentLevel,
                        'Requisition Date' => $requisition->requisition_date ? $requisition->requisition_date->format('Y-m-d') : 'N/A',
                        'Rejected By' => Auth::user()->name,
                        'Rejection Reason' => $request->comments ?? 'No reason provided',
                        'Rejected At' => now()->format('Y-m-d H:i:s')
                    ]);
                }
            }

            DB::commit();

            // Send notification to next approver if moving to next level
            if ($request->action === 'approve' && $nextLevel) {
                $this->sendApprovalNotification($requisition, $nextLevel);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $requisition->status,
                'current_approval_level' => $requisition->current_approval_level
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error processing store requisition {$requisition->voucher_no}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the store requisition. Please try again.'
            ], 500);
        }
    }

    /**
     * Get next approval level
     */
    private function getNextApprovalLevel($approvalSettings, $currentLevel)
    {
        if (!$approvalSettings) {
            return null;
        }

        // Find next enabled level
        for ($level = $currentLevel + 1; $level <= 5; $level++) {
            if ($approvalSettings->{"level_{$level}_enabled"}) {
                return $level;
            }
        }

        return null; // No more levels
    }

    /**
     * Get approver for a specific level
     */
    private function getApproverForLevel($approvalSettings, $level)
    {
        if (!$approvalSettings) {
            return null;
        }

        $levelUserId = $approvalSettings->{"level_{$level}_user_id"};
        return $levelUserId ?: null;
    }

    /**
     * Resolve store requisition by ID or hash ID
     */
    private function resolveStoreRequisition($id, $withRelations = [])
    {
        $query = StoreRequisition::query();
        
        if (!empty($withRelations)) {
            $query->with($withRelations);
        }
        
        // Handle both regular ID and hash ID
        if (is_numeric($id)) {
            return $query->findOrFail($id);
        } else {
            // Try to decode hash ID
            $requisition = $query->where('company_id', Auth::user()->company_id)
                ->get()
                ->filter(function ($req) use ($id) {
                    return $req->hash_id === $id;
                })
                ->first();
            
            if (!$requisition) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }
            
            return $requisition;
        }
    }
    private function sendApprovalNotification($requisition, $level)
    {
        try {
            $approvalSettings = StoreRequisitionApprovalSettings::where('company_id', $requisition->company_id)->first();
            $approverId = $this->getApproverForLevel($approvalSettings, $level);
            
            if ($approverId) {
                $approver = \App\Models\User::find($approverId);
                if ($approver) {
                    // Log notification (you can extend this to send email or dashboard notifications)
                    Log::info("Notification sent to {$approver->name} for store requisition {$requisition->requisition_number} approval at level {$level}");
                    
                    // TODO: Implement actual notification sending (email, dashboard, SMS)
                    // You can use Laravel's notification system here
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to send approval notification: " . $e->getMessage());
        }
    }

    /**
     * Check if user can approve the requisition
     */
    private function canUserApprove(StoreRequisition $requisition, $user)
    {
        if (!$requisition->canBeApproved()) {
            return false;
        }

        $approvalSettings = StoreRequisitionApprovalSettings::where('company_id', $requisition->company_id)->first();
        
        if (!$approvalSettings) {
            return false;
        }

        $currentLevel = $requisition->current_approval_level;
        
        // Check if current level is enabled
        if (!$approvalSettings->{"level_{$currentLevel}_enabled"}) {
            return false;
        }

        // Check if user is assigned to current approval level
        $levelUserId = $approvalSettings->{"level_{$currentLevel}_user_id"};
        $levelRoleId = $approvalSettings->{"level_{$currentLevel}_role_id"};
        
        if ($levelUserId && $levelUserId == $user->id) {
            return true;
        }
        
        if ($levelRoleId && $user->hasRole($levelRoleId)) {
            return true;
        }
        
        return false;
    }

    /**
     * Get requisition approval history
     */
    public function getApprovalHistory($id)
    {
        try {
            $requisition = $this->resolveStoreRequisition($id, ['approvals.approver']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Store requisition not found.'
            ], 404);
        }
        
        $approvals = $requisition->approvals->map(function ($approval) {
            return [
                'level' => $approval->approval_level,
                'approver' => $approval->approver ? $approval->approver->name : 'System',
                'action' => ucfirst($approval->action),
                'comments' => $approval->comments,
                'action_date' => $approval->action_date ? $approval->action_date->format('Y-m-d H:i:s') : null,
                'action_badge' => $approval->action_badge
            ];
        });

        return response()->json([
            'success' => true,
            'approvals' => $approvals
        ]);
    }

    /**
     * Cancel a store requisition
     */
    public function cancel(Request $request, $id)
    {
        try {
            $requisition = $this->resolveStoreRequisition($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Store requisition not found.'
            ], 404);
        }
        
        // Only creator or admin can cancel
        if ($requisition->created_by !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to cancel this store requisition.'
            ], 403);
        }

        // Can only cancel pending requisitions
        if (!in_array($requisition->status, ['pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requisitions can be cancelled.'
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:500'
        ], [
            'reason.required' => 'Cancellation reason is required.',
            'reason.min' => 'Cancellation reason must be at least 5 characters.'
        ]);

        try {
            DB::beginTransaction();

            $requisition->update([
                'status' => 'cancelled',
                'remarks' => $request->reason,
            ]);

            // Record cancellation as an approval action
            StoreRequisitionApproval::create([
                'store_requisition_id' => $requisition->id,
                'approval_level' => $requisition->current_approval_level,
                'approver_id' => Auth::id(),
                'action' => 'rejected', // Use rejected for cancellation
                'action_date' => now(),
                'comments' => 'Cancelled: ' . $request->reason,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            Log::info("Store requisition {$requisition->voucher_no} cancelled by user " . Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Store requisition cancelled successfully.',
                'status' => $requisition->status
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error cancelling store requisition {$requisition->voucher_no}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling the store requisition. Please try again.'
            ], 500);
        }
    }

    /**
     * Resubmit a rejected store requisition
     */
    public function resubmit(Request $request, $id)
    {
        try {
            $requisition = $this->resolveStoreRequisition($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Store requisition not found.'
            ], 404);
        }
        
        // Only creator can resubmit
        if ($requisition->created_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to resubmit this store requisition.'
            ], 403);
        }

        // Can only resubmit rejected requisitions
        if ($requisition->status !== 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Only rejected requisitions can be resubmitted.'
            ], 400);
        }

        $request->validate([
            'comments' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Reset to pending status and start approval workflow again
            $approvalSettings = StoreRequisitionApprovalSettings::where('company_id', $requisition->company_id)->first();
            
            $firstLevel = 1;
            if ($approvalSettings) {
                // Find first enabled approval level
                for ($level = 1; $level <= 5; $level++) {
                    if ($approvalSettings->{"level_{$level}_enabled"}) {
                        $firstLevel = $level;
                        break;
                    }
                }
            }

            $requisition->update([
                'status' => 'pending',
                'current_approval_level' => $firstLevel,
                'remarks' => $request->comments,
            ]);

            // Record resubmission as an approval action
            StoreRequisitionApproval::create([
                'store_requisition_id' => $requisition->id,
                'approval_level' => 0,
                'approver_id' => Auth::id(),
                'action' => 'pending', // Use pending for resubmission
                'action_date' => now(),
                'comments' => 'Resubmitted: ' . ($request->comments ?: 'Store requisition resubmitted for approval'),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            Log::info("Store requisition {$requisition->voucher_no} resubmitted by user " . Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Store requisition resubmitted successfully.',
                'status' => $requisition->status,
                'current_approval_level' => $requisition->current_approval_level
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error resubmitting store requisition {$requisition->voucher_no}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resubmitting the store requisition. Please try again.'
            ], 500);
        }
    }

    /**
     * Return issued items from a fully issued store requisition
     */
    public function return(Request $request, $id)
    {
        try {
            Log::info("Store requisition return request received", [
                'id' => $id,
                'user_id' => Auth::id()
            ]);
            
            $requisition = $this->resolveStoreRequisition($id);
            
            // Validate requisition status - allow both fully and partially issued requisitions
            if (!in_array($requisition->status, ['fully_issued', 'partially_issued'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only issued requisitions can have items returned.'
                ], 400);
            }

            // Validate request - check for required fields
            $request->validate([
                'return_reason' => 'required|string|max:500',
                'return_items' => 'required|array|min:1'
            ]);

            // Validate individual items
            $returnItems = $request->return_items;
            if (empty($returnItems)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one item to return.'
                ], 422);
            }

            // Check that at least one item has a valid quantity
            $hasValidQuantity = false;
            foreach ($returnItems as $itemId => $itemData) {
                if (!isset($itemData['quantity'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quantity is required for each item.'
                    ], 422);
                }

                $quantity = (float) $itemData['quantity'];
                if ($quantity > 0) {
                    $hasValidQuantity = true;
                }

                if (!isset($itemData['inventory_item_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Inventory item ID is required for each item.'
                    ], 422);
                }

                // Verify inventory item exists
                $inventoryItem = \App\Models\Inventory\Item::find($itemData['inventory_item_id']);
                if (!$inventoryItem) {
                    return response()->json([
                        'success' => false,
                        'message' => "Inventory item {$itemData['inventory_item_id']} does not exist."
                    ], 422);
                }
            }

            if (!$hasValidQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter at least one item quantity to return.'
                ], 422);
            }

            DB::beginTransaction();

            $totalReturnAmount = 0;
            $user = Auth::user();
            
            // Get branch ID - prefer session branch_id, fallback to user branch_id, then requisition branch_id
            $branchId = session('branch_id') ?? $user->branch_id ?? $requisition->branch_id;
            
            if (!$branchId) {
                throw new \Exception('Unable to determine branch ID for return processing.');
            }
            
            Log::info("Return processing started", [
                'requisition_id' => $requisition->id,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'return_items_count' => count($request->return_items)
            ]);
            
            // Get all store issues for this requisition
            $storeIssues = \App\Models\StoreIssue::where('store_requisition_id', $requisition->id)
                ->where('status', 'issued')
                ->get();

            if ($storeIssues->isEmpty()) {
                throw new \Exception('No issued items found for this requisition.');
            }

            // Collect items to return for saving later
            $itemsToReturn = [];

            foreach ($request->return_items as $itemId => $returnData) {
                $returnQuantity = (float) $returnData['quantity'];
                
                if ($returnQuantity <= 0) {
                    continue; // Skip items with zero quantity
                }

                // Find the requisition item
                $requisitionItem = $requisition->items()->find($itemId);
                if (!$requisitionItem) {
                    throw new \Exception("Requisition item not found: {$itemId}");
                }

                // Validate return quantity doesn't exceed issued quantity
                if ($returnQuantity > $requisitionItem->quantity_issued) {
                    throw new \Exception("Return quantity for {$requisitionItem->product->name} cannot exceed issued quantity.");
                }

                $product = $requisitionItem->product;
                $unitCost = $product->cost_price ?? 0;
                $itemReturnAmount = $returnQuantity * $unitCost;
                $totalReturnAmount += $itemReturnAmount;

                // Collect item data for later saving
                $itemsToReturn[] = [
                    'requisition_item_id' => $requisitionItem->id,
                    'product_id' => $product->id,
                    'inventory_item_id' => $returnData['inventory_item_id'],
                    'quantity_returned' => $returnQuantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $itemReturnAmount,
                    'product_name' => $product->name
                ];

                // Find the original store issue that was made
                $storeIssue = $storeIssues->first(); // Get the first store issue for this requisition
                
                // Get the location from the inventory movement records for this store issue
                $originalMovement = \App\Models\Inventory\Movement::where('reference_type', 'store_issue')
                    ->where('reference_id', $storeIssue->id)
                    ->where('item_id', $returnData['inventory_item_id'])
                    ->where('movement_type', 'adjustment_out')
                    ->first();
                
                if (!$originalMovement) {
                    throw new \Exception("Original inventory movement not found for {$product->name}");
                }
                
                $originalLocationId = $originalMovement->location_id;

                // Validate all required fields before creating movement
                if (!$branchId || !$originalLocationId || !$returnData['inventory_item_id'] || !$user->id) {
                    throw new \Exception("Missing required data for inventory movement creation");
                }

                // Create return inventory movement (adjustment_in to original location)
                $movementData = [
                    'branch_id' => $branchId, // Use the determined branch ID
                    'location_id' => $originalLocationId, // Return to original issue location
                    'item_id' => $returnData['inventory_item_id'],
                    'user_id' => $user->id,
                    'movement_type' => 'adjustment_in',
                    'quantity' => $returnQuantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $itemReturnAmount,
                    'reference_type' => 'store_requisition_return',
                    'reference_id' => $requisition->id,
                    'movement_date' => now(),
                    'reason' => "Return of items from requisition #{$requisition->requisition_number}: {$request->return_reason}",
                    'notes' => "Returned item: {$product->name}"
                ];
                
                Log::info("Creating inventory movement", $movementData);
                
                \App\Models\Inventory\Movement::create($movementData);

                // Create accounting entries - Credit Cost, Debit Inventory
                $this->createReturnAccountingEntries(
                    $user->company_id,
                    $branchId, // Use the determined branch ID
                    $itemReturnAmount,
                    $product,
                    $requisition,
                    $request->return_reason
                );

                // Update requisition item issued quantity
                $requisitionItem->update([
                    'quantity_issued' => $requisitionItem->quantity_issued - $returnQuantity
                ]);

                Log::info("Returned item: {$product->name}, Quantity: {$returnQuantity}, Amount: {$itemReturnAmount}");
            }

            // Update requisition status if all items are returned
            $totalIssuedAfterReturn = $requisition->items()->sum('quantity_issued');
            if ($totalIssuedAfterReturn == 0) {
                $requisition->update(['status' => 'approved']);
            } else {
                $requisition->update(['status' => 'partially_issued']);
            }

            // Create return record for audit trail
            $storeRequisitionReturn = \App\Models\StoreRequisitionReturn::create([
                'store_requisition_id' => $requisition->id,
                'return_date' => now(),
                'return_reason' => $request->return_reason,
                'total_return_amount' => $totalReturnAmount,
                'processed_by' => $user->id,
                'company_id' => $user->company_id,
                'branch_id' => $branchId // Use the determined branch ID
            ]);

            // Save individual returned items
            foreach ($itemsToReturn as $item) {
                \App\Models\StoreRequisitionReturnItem::create([
                    'store_requisition_return_id' => $storeRequisitionReturn->id,
                    'store_requisition_item_id' => $item['requisition_item_id'],
                    'product_id' => $item['product_id'],
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity_returned' => $item['quantity_returned'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $item['total_cost']
                ]);
            }

            DB::commit();

            Log::info("Store requisition {$requisition->requisition_number} items returned successfully. Total amount: {$totalReturnAmount}");

            return response()->json([
                'success' => true,
                'message' => "Items returned successfully. Total return amount: " . number_format($totalReturnAmount, 2),
                'total_return_amount' => $totalReturnAmount,
                'new_status' => $requisition->fresh()->status
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error returning store requisition items: " . $e->getMessage(), [
                'requisition_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create accounting entries for item returns
     */
    private function createReturnAccountingEntries($companyId, $branchId, $amount, $product, $requisition, $reason)
    {
        // This would integrate with your accounting system
        // Credit: Cost Account (reduce cost)
        // Debit: Inventory Account (increase inventory)
        
        try {
            // For now, we'll create simple accounting records in the database
            // This can be enhanced when full accounting system is implemented
            
            DB::table('accounting_entries')->insert([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'entry_date' => now(),
                'reference_type' => 'store_requisition_return',
                'reference_id' => $requisition->id,
                'description' => "Return of items from requisition #{$requisition->requisition_number}: {$reason}",
                'debit_account' => 'Inventory Account',
                'credit_account' => 'Cost Account', 
                'amount' => $amount,
                'product_name' => $product->name,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => Auth::id()
            ]);

            Log::info("Accounting entry created for return", [
                'amount' => $amount,
                'product' => $product->name,
                'description' => 'Credit Cost, Debit Inventory'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create accounting entries for return: " . $e->getMessage());
            // Don't throw - allow the return to proceed even if accounting fails
        }
    }
}
