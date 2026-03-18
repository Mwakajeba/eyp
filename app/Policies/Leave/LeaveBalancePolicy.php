<?php

namespace App\Policies\Leave;

use App\Models\Hr\LeaveBalance;
use App\Models\User;

class LeaveBalancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view leave balances') ||
               $user->hasPermissionTo('manage leave balances') ||
               $user->hasPermissionTo('view hr payroll');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveBalance $leaveBalance): bool
    {
        // Can view own balance
        if ($user->employee_id === $leaveBalance->employee_id) {
            return $user->hasPermissionTo('view employee leave balance');
        }

        // Can view if has general permission
        return $user->hasPermissionTo('view leave balances') ||
               $user->hasPermissionTo('manage leave balances');
    }

    /**
     * Determine whether the user can update the model (adjust balance).
     */
    public function update(User $user, LeaveBalance $leaveBalance = null): bool
    {
        return $user->hasPermissionTo('adjust leave balance') ||
               $user->hasPermissionTo('edit leave balance') ||
               $user->hasPermissionTo('manage leave balances') ||
               $user->hasPermissionTo('manage hr payroll');
    }
}

