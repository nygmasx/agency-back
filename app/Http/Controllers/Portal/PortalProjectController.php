<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = $request->client;

        $projects = $client->projects()
            ->where('status', '!=', 'archived')
            ->withCount(['tasks', 'pendingTasks', 'completedTasks'])
            ->get();

        return response()->json([
            'projects' => $projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status->value,
                'tasks_count' => $project->tasks_count,
                'pending_tasks_count' => $project->pending_tasks_count,
                'completed_tasks_count' => $project->completed_tasks_count,
                'progress' => $project->tasks_count > 0
                    ? round(($project->completed_tasks_count / $project->tasks_count) * 100)
                    : 0,
                'created_at' => $project->created_at,
            ]),
        ]);
    }

    public function tasks(Request $request, Project $project): JsonResponse
    {
        $client = $request->client;

        if ($project->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tasks = $project->tasks()
            ->with(['assignee'])
            ->orderBy('position')
            ->get();

        return response()->json([
            'tasks' => $tasks->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status->value,
                'priority' => $task->priority->value,
                'progress' => $task->progress,
                'due_date' => $task->due_date?->format('Y-m-d'),
                'is_overdue' => $task->isOverdue(),
                'is_recurring' => $task->isRecurring(),
                'recurrence_rule' => $task->recurrence_rule,
                'position' => $task->position,
                'assignee' => $task->assignee ? [
                    'id' => $task->assignee->id,
                    'name' => $task->assignee->name,
                ] : null,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ]),
        ]);
    }
}
