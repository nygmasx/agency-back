<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'path',
        'size',
        'mime_type',
        'uploaded_by_type',
        'uploaded_by_id',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getUploaderAttribute(): ?object
    {
        if ($this->uploaded_by_type === 'team') {
            return User::find($this->uploaded_by_id);
        }

        if ($this->uploaded_by_type === 'collaborator') {
            return ClientCollaborator::find($this->uploaded_by_id);
        }

        return null;
    }
}
