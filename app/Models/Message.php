<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'author_type',
        'author_id',
        'content',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function getAuthorAttribute(): array
    {
        if ($this->author_type === 'team') {
            $user = User::find($this->author_id);
            return [
                'id' => $user?->id,
                'name' => $user?->name ?? 'Unknown',
                'type' => 'team',
            ];
        }

        if ($this->author_type === 'collaborator') {
            $collaborator = ClientCollaborator::find($this->author_id);
            return [
                'id' => $collaborator?->id,
                'name' => $collaborator?->name ?? 'Unknown',
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
