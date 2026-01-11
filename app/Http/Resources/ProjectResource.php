<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'client' => new ClientResource($this->whenLoaded('client')),
            'tasks_count' => $this->whenCounted('tasks'),
            'pending_tasks_count' => $this->whenCounted('pendingTasks'),
            'completed_tasks_count' => $this->whenCounted('completedTasks'),
            'progress' => $this->when(
                $this->relationLoaded('tasks'),
                fn () => $this->progress()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
