<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = $request->client;

        $tasks = Task::whereHas('project', fn ($q) => $q->where('client_id', $client->id))
            ->with(['assignee', 'project'])
            ->orderBy('position')
            ->get();

        return response()->json([
            'tasks' => $tasks->map(fn ($task) => $this->formatTask($task)),
        ]);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $client = $request->client;

        if (!$task->project || $task->project->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task->load(['assignee', 'project']);

        return response()->json([
            'task' => $this->formatTask($task),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if ($project->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$this->hasPermission($collaborator, 'edit')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:todo,in_progress,review,done'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'is_recurring' => ['sometimes', 'boolean'],
            'recurrence_rule' => ['nullable', 'string'],
        ]);

        $task = Task::create([
            'team_id' => $project->client->team_id,
            'project_id' => $project->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'todo',
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? null,
            'progress' => $validated['progress'] ?? 0,
            'recurrence_rule' => $validated['is_recurring'] ?? false ? $validated['recurrence_rule'] : null,
            'created_by' => $project->client->team->owner_id,
            'position' => Task::where('project_id', $project->id)->max('position') + 1,
        ]);

        $task->load(['assignee', 'project']);

        return response()->json([
            'task' => $this->formatTask($task),
        ], 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if (!$task->project || $task->project->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$this->hasPermission($collaborator, 'edit')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:todo,in_progress,review,done'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'is_recurring' => ['sometimes', 'boolean'],
            'recurrence_rule' => ['nullable', 'string'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);

        if (isset($validated['is_recurring'])) {
            $validated['recurrence_rule'] = $validated['is_recurring'] ? ($validated['recurrence_rule'] ?? null) : null;
            unset($validated['is_recurring']);
        }

        $task->update($validated);
        $task->load(['assignee', 'project']);

        return response()->json([
            'task' => $this->formatTask($task),
        ]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if (!$task->project || $task->project->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$this->hasPermission($collaborator, 'edit')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    protected function hasPermission($collaborator, string $permission): bool
    {
        $permissions = $collaborator->permissions ?? ['view'];
        return in_array($permission, $permissions);
    }

    protected function formatTask(Task $task): array
    {
        return [
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
            'project' => $task->project ? [
                'id' => $task->project->id,
                'name' => $task->project->name,
            ] : null,
            'assignee' => $task->assignee ? [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
            ] : null,
            'created_at' => $task->created_at,
            'updated_at' => $task->updated_at,
        ];
    }
}
