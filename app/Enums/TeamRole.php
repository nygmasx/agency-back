<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
            self::Viewer => 'Viewer',
        };
    }

    public function canManageTeam(): bool
    {
        return in_array($this, [self::Owner, self::Admin]);
    }

    public function canManageMembers(): bool
    {
        return in_array($this, [self::Owner, self::Admin]);
    }

    public function canCreateTasks(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Member]);
    }

    public function canEditTasks(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Member]);
    }

    public function canDeleteTasks(): bool
    {
        return in_array($this, [self::Owner, self::Admin]);
    }
}
