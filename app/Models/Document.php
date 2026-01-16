<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'client_id',
        'team_id',
        'title',
        'content',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function createdByCollaborator(): BelongsTo
    {
        return $this->belongsTo(ClientCollaborator::class, 'created_by_id');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function updatedByCollaborator(): BelongsTo
    {
        return $this->belongsTo(ClientCollaborator::class, 'updated_by_id');
    }

    public function getCreatedByAttribute(): ?array
    {
        if ($this->created_by_type === 'user') {
            $user = $this->createdByUser;
            return $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'type' => 'user',
            ] : null;
        }

        if ($this->created_by_type === 'collaborator') {
            $collaborator = $this->createdByCollaborator;
            return $collaborator ? [
                'id' => $collaborator->id,
                'name' => $collaborator->name,
                'type' => 'collaborator',
            ] : null;
        }

        return null;
    }

    public function getUpdatedByAttribute(): ?array
    {
        if (!$this->updated_by_id) {
            return null;
        }

        if ($this->updated_by_type === 'user') {
            $user = $this->updatedByUser;
            return $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'type' => 'user',
            ] : null;
        }

        if ($this->updated_by_type === 'collaborator') {
            $collaborator = $this->updatedByCollaborator;
            return $collaborator ? [
                'id' => $collaborator->id,
                'name' => $collaborator->name,
                'type' => 'collaborator',
            ] : null;
        }

        return null;
    }
}
