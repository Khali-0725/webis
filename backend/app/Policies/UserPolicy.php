<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function view(User $actor, User $target): bool
    {
        return $actor->isAdmin()
            && ($actor->isSuperAdmin() || ! in_array($target->role, [UserRole::Admin, UserRole::SuperAdmin], true));
    }

    public function manage(User $actor, User $target): bool
    {
        return $this->view($actor, $target) && $target->id !== $actor->id;
    }

    public function forceDelete(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin()
            && $target->role !== UserRole::SuperAdmin
            && $target->id !== $actor->id;
    }
}
