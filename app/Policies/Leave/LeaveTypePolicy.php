<?php

namespace App\Policies\Leave;

use App\Models\Hr\LeaveType;
use App\Models\User;

class LeaveTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view leave types') ||
               $user->hasPermissionTo('view hr payroll') ||
               $user->hasPermissionTo('manage leave types') ||
               $user->hasPermissionTo('manage hr payroll');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveType $leaveType): bool
    {
        return $user->hasPermissionTo('view leave type details') ||
               $user->hasPermissionTo('view leave types') ||
               $user->hasPermissionTo('manage leave types') ||
               $user->hasPermissionTo('manage hr payroll');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create leave type') ||
               $user->hasPermissionTo('manage leave types') ||
               $user->hasPermissionTo('manage hr payroll');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveType $leaveType): bool
    {
        return $user->hasPermissionTo('edit leave type') ||
               $user->hasPermissionTo('manage leave types') ||
               $user->hasPermissionTo('manage hr payroll');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveType $leaveType): bool
    {
        return $user->hasPermissionTo('delete leave type') ||
               $user->hasPermissionTo('manage leave types') ||
               $user->hasPermissionTo('manage hr payroll');
    }
}
