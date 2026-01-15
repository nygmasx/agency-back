<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'collaborator_id',
        'content',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(ClientCollaborator::class);
    }

    public function getAuthorAttribute(): array
    {
        if ($this->user_id) {
            $user = $this->user;
            if ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'type' => 'user',
                ];
            }
        }

        if ($this->collaborator_id) {
            $collaborator = $this->collaborator;
            if ($collaborator) {
                return [
                    'id' => $collaborator->id,
                    'name' => $collaborator->name,
                    'type' => 'collaborator',
                ];
            }
        }

        return [
            'id' => null,
            'name' => 'Unknown',
            'type' => 'unknown',
        ];
    }
}
