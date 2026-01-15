<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'project_id',
        'client_id',
        'title',
        'description',
        'assigned_to',
        'assignee_type',
        'created_by',
        'status',
        'priority',
        'progress',
        'due_date',
        'recurrence_rule',
        'parent_task_id',
        'position',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class,
        'progress' => 'integer',
        'due_date' => 'date',
        'position' => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function collaboratorAssignee(): BelongsTo
    {
        return $this->belongsTo(ClientCollaborator::class, 'assigned_to');
    }

    public function getAssigneeInfoAttribute(): ?array
    {
        if (!$this->assigned_to) {
            return null;
        }

        if ($this->assignee_type === 'collaborator') {
            $collaborator = $this->collaboratorAssignee;
            return $collaborator ? [
                'id' => $collaborator->id,
                'name' => $collaborator->name,
                'type' => 'collaborator',
            ] : null;
        }

        $user = $this->assignee;
        return $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'type' => 'user',
        ] : null;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function childTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(TaskFile::class);
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_rule !== null;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->status !== TaskStatus::Done;
    }

    public function scopeForTeam($query, Team $team)
    {
        return $query->where('team_id', $team->id)->whereNull('project_id');
    }

    public function scopeForProject($query, Project $project)
    {
        return $query->where('project_id', $project->id);
    }

    public function scopeAssignedTo($query, User $user)
    {
        return $query->where('assigned_to', $user->id);
    }

    public function scopeWithStatus($query, TaskStatus|array $status)
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }

        return $query->where('status', $status);
    }

    public function scopeWithPriority($query, TaskPriority|array $priority)
    {
        if (is_array($priority)) {
            return $query->whereIn('priority', $priority);
        }

        return $query->where('priority', $priority);
    }

    public function scopeDueBetween($query, $from, $to)
    {
        return $query->whereBetween('due_date', [$from, $to]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', TaskStatus::Done);
    }
}
