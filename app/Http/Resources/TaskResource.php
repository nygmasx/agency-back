<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'priority_color' => $this->priority->color(),
            'progress' => $this->progress,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'is_overdue' => $this->isOverdue(),
            'is_recurring' => $this->isRecurring(),
            'recurrence_rule' => $this->recurrence_rule,
            'position' => $this->position,
            'assignee' => $this->assignee_info,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'client_id' => $this->client_id,
            'team_id' => $this->team_id,
            'project_id' => $this->project_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
