<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'collaborator_id',
        'email_enabled',
        'email_task_assigned',
        'email_task_completed',
        'email_task_comment',
        'email_task_due_soon',
        'email_daily_digest',
        'push_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'email_task_assigned' => 'boolean',
        'email_task_completed' => 'boolean',
        'email_task_comment' => 'boolean',
        'email_task_due_soon' => 'boolean',
        'email_daily_digest' => 'boolean',
        'push_enabled' => 'boolean',
    ];

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(ClientCollaborator::class);
    }

    public static function getOrCreateForCollaborator(ClientCollaborator $collaborator): self
    {
        return self::firstOrCreate(
            ['collaborator_id' => $collaborator->id],
            [
                'email_enabled' => true,
                'email_task_assigned' => true,
                'email_task_completed' => true,
                'email_task_comment' => true,
                'email_task_due_soon' => true,
                'email_daily_digest' => false,
                'push_enabled' => false,
            ]
        );
    }
}
