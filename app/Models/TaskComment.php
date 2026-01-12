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
            return [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'type' => 'team',
            ];
        }

        if ($this->collaborator_id) {
            return [
                'id' => $this->collaborator->id,
                'name' => $this->collaborator->name,
                'type' => 'collaborator',
            ];
        }

        return [
            'id' => null,
            'name' => 'Unknown',
            'type' => 'unknown',
        ];
    }
}
