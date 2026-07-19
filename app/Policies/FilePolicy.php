<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, File $file): bool
    {
        return $user->tenant_id === $file->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, File $file): bool
    {
        return $user->tenant_id === $file->tenant_id;
    }

    public function delete(User $user, File $file): bool
    {
        return $user->tenant_id === $file->tenant_id;
    }
}
