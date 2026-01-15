<?php

namespace App\Models;

use App\Enums\CollaboratorRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ClientCollaborator extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'email',
        'name',
        'token',
        'permissions',
        'access_type',
        'role',
        'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'role' => CollaboratorRole::class,
    ];

    protected $hidden = [
        'token',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClientCollaborator $collaborator) {
            if (empty($collaborator->token)) {
                $collaborator->token = Str::random(64);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasPermission(string $permission): bool
    {
        return match ($permission) {
            'view' => $this->role->canView(),
            'comment' => $this->role->canComment(),
            'edit' => $this->role->canEdit(),
            default => false,
        };
    }

    public function canView(): bool
    {
        return $this->role->canView();
    }

    public function canComment(): bool
    {
        return $this->role->canComment();
    }

    public function canEdit(): bool
    {
        return $this->role->canEdit();
    }
}
