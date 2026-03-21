<?php

namespace App\Policies;

use App\Models\ImprestRequest;
use App\Models\User;

class ImprestRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin'])
            || $user->can('view all imprests')
            || $user->can('view own imprests')
            || $user->can('view imprest requests');
    }

    public function view(User $user, ImprestRequest $imprest): bool
    {
        if ($user->company_id && $imprest->company_id && $imprest->company_id !== $user->company_id) {
            return false;
        }

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if ($user->can('view all imprests')) {
            return true;
        }

        // Own imprest — either the employee or the creator
        return $imprest->employee_id === $user->id
            || $imprest->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create imprest request');
    }

    public function update(User $user, ImprestRequest $imprest): bool
    {
        if ($imprest->status !== 'pending') {
            return false;
        }

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        return $user->id === $imprest->created_by && $user->can('edit imprest request');
    }

    public function delete(User $user, ImprestRequest $imprest): bool
    {
        if ($imprest->status !== 'pending') {
            return false;
        }

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        return $user->id === $imprest->created_by && $user->can('delete imprest request');
    }
}
