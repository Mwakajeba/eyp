<?php

namespace App\Policies\Leave;

use App\Models\Hr\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view leave requests') ||
               $user->hasPermissionTo('manage leave requests') ||
               $user->hasPermissionTo('view hr payroll');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        // First check company_id - users can only view requests from their company
        if ($user->company_id && $leaveRequest->company_id && $leaveRequest->company_id !== $user->company_id) {
            return false;
        }

        // Check for super-admin or admin role first
        try {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'admin'])) {
                return true;
            }
        } catch (\Exception $e) {
            // Continue to permission checks if role check fails
        }

        // Load employee relationship if not already loaded
        if (!$user->relationLoaded('employee')) {
            $user->load('employee');
        }

        // Can view own requests (no permission check needed for own requests)
        $userEmployeeId = $user->employee_id;
        if ($userEmployeeId && $userEmployeeId === $leaveRequest->employee_id) {
            return true;
        }

        // Can view if has manage permission (can view all requests in their company)
        if (
            $user->hasPermissionTo('manage leave requests') ||
            $user->hasPermissionTo('manage hr payroll')
        ) {
            return true;
        }

        // Can view if has viewAny permission (can view requests list)
        if ($this->viewAny($user)) {
            return true;
        }

        // Can view if has view permission
        if (
            $user->hasPermissionTo('view leave request details') ||
            $user->hasPermissionTo('view leave requests')
        ) {
            return true;
        }

        // Can view if manager of the employee (and has approve permission)
        if (
            $userEmployeeId &&
            $leaveRequest->employee &&
            $leaveRequest->employee->reports_to === $userEmployeeId &&
            (
                $user->hasPermissionTo('approve leave request') ||
                $user->hasPermissionTo('manage leave requests')
            )
        ) {
            return true;
        }

        // Can view if user is an approver for this request
        if ($leaveRequest->relationLoaded('approvals')) {
            $isApprover = $leaveRequest->approvals->contains(
                function ($approval) use ($userEmployeeId) {
                    return $approval->approver_id === $userEmployeeId;
                }
            );
            if ($isApprover) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Check for super-admin or admin role (try-catch for safety)
        try {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'admin'])) {
                return true;
            }
        } catch (\Exception $e) {
            // Continue to permission checks if role check fails
        }

        // HR/Admin can create requests for others without being employees themselves
        // Super-admin should have all permissions, so this will catch them too
        if (
            $user->hasPermissionTo('manage leave requests') ||
            $user->hasPermissionTo('manage hr payroll') ||
            $user->hasPermissionTo('create leave request')
        ) {
            return true;
        }

        // Regular employees need both employee record and permission
        if ($user->employee_id !== null && $user->hasPermissionTo('create leave request')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        // Can only update draft requests
        if ($leaveRequest->status !== 'draft') {
            return false;
        }

        // Check for super-admin or admin role first
        try {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'admin'])) {
                return true;
            }
        } catch (\Exception $e) {
            // Continue to permission checks if role check fails
        }

        // Can update own draft requests if has permission
        if ($user->employee_id === $leaveRequest->employee_id) {
            return $user->hasPermissionTo('edit leave request');
        }

        // Can update if has manage permission
        return $user->hasPermissionTo('manage leave requests') ||
               $user->hasPermissionTo('manage hr payroll');
    }

    /**
     * Determine whether the user can submit the leave request for approval.
     */
    public function submit(User $user, LeaveRequest $leaveRequest): bool
    {
        // Can only submit draft requests
        if ($leaveRequest->status !== 'draft') {
            return false;
        }

        // Check for super-admin or admin role first
        try {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'admin'])) {
                return true;
            }
        } catch (\Exception $e) {
            // Continue to permission checks if role check fails
        }

        // Load employee relationship if not already loaded
        if (!$user->relationLoaded('employee')) {
            $user->load('employee');
        }

        // Can submit own draft requests (no permission check needed for own requests)
        $userEmployeeId = $user->employee_id;
        if ($userEmployeeId && $userEmployeeId === $leaveRequest->employee_id) {
            return true;
        }

        // Can submit if has manage permission
        if ($user->hasPermissionTo('manage leave requests') ||
            $user->hasPermissionTo('manage hr payroll')) {
            return true;
        }

        // Can submit if has submit or edit permission (for submitting others' requests)
        return $user->hasPermissionTo('submit leave request') ||
               $user->hasPermissionTo('edit leave request');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        // Can only delete draft requests
        if ($leaveRequest->status !== 'draft') {
            return false;
        }

        // Can delete own draft requests if has permission
        if ($user->employee_id === $leaveRequest->employee_id) {
            return $user->hasPermissionTo('delete leave request');
        }

        // Can delete if has manage permission
        return $user->hasPermissionTo('manage leave requests') ||
               $user->hasPermissionTo('manage hr payroll');
    }

    /**
     * Determine whether the user can approve the leave request.
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        // Cannot approve own request
        if ($user->employee_id === $leaveRequest->employee_id) {
            return false;
        }

        // Check if user has explicit approval permission
        if (
            $user->hasPermissionTo('approve leave request') ||
            $user->hasPermissionTo('manage leave requests')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reject the leave request.
     */
    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        // Cannot reject own request
        if ($user->employee_id === $leaveRequest->employee_id) {
            return false;
        }

        return $user->hasPermissionTo('reject leave request') ||
               $user->hasPermissionTo('manage leave requests');
    }

    /**
     * Determine whether the user can return the leave request for editing.
     */
    public function returnForEdit(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('return leave request') ||
               $user->hasPermissionTo('manage leave requests');
    }

    /**
     * Determine whether the user can cancel the leave request.
     */
    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        // Can cancel own request if it's cancellable and has permission
        if ($user->employee_id === $leaveRequest->employee_id && $leaveRequest->isCancellable()) {
            return $user->hasPermissionTo('cancel leave request');
        }

        // Can cancel with manage permission
        return $user->hasPermissionTo('manage leave requests') ||
               $user->hasPermissionTo('manage hr payroll');
    }
}
