<?php

namespace App\Models;

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
        'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
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
        if ($this->permissions === null) {
            return true;
        }

        return in_array($permission, $this->permissions);
    }
}
