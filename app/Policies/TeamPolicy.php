<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $team->hasMember($user);
    }

    public function update(User $user, Team $team): bool
    {
        $role = $team->getMemberRole($user);

        return $role?->canManageTeam() ?? false;
    }

    public function delete(User $user, Team $team): bool
    {
        return $team->isOwner($user);
    }

    public function manageMembers(User $user, Team $team): bool
    {
        $role = $team->getMemberRole($user);

        return $role?->canManageMembers() ?? false;
    }
}
