<?php

namespace App\Policies;

use App\Models\Qode;
use App\Models\User;

class QodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Qode $qode): bool
    {
        return $user->tenant_id === $qode->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, Qode $qode): bool
    {
        return $user->tenant_id === $qode->tenant_id;
    }

    public function delete(User $user, Qode $qode): bool
    {
        return $user->tenant_id === $qode->tenant_id;
    }
}
