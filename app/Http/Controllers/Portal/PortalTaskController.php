<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientCollaborator;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = $request->client;
        $clientId = $client->id;

        $tasks = Task::where('client_id', $clientId)
            ->orWhereHas('project', fn ($q) => $q->where('client_id', $clientId))
            ->with(['assignee', 'collaboratorAssignee', 'project'])
            ->orderBy('position')
            ->get();

        return response()->json([
            'tasks' => $tasks->map(fn ($task) => $this->formatTask($task)),
        ]);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $client = $request->client;

        if (!$this->taskBelongsToClient($task, $client->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task->load(['assignee', 'collaboratorAssignee', 'project']);

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
            'assigned_to' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'recurrence_rule' => ['nullable', 'string', 'in:daily,weekly,monthly'],
        ]);

        $assigneeData = $this->resolveAssignee($validated['assigned_to'] ?? null, $client->id);

        $task = Task::create([
            'team_id' => $project->client->team_id,
            'project_id' => $project->id,
            'client_id' => $client->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'todo',
            'priority' => $validated['priority'] ?? 'medium',
            'assigned_to' => $assigneeData['assigned_to'],
            'assignee_type' => $assigneeData['assignee_type'],
            'due_date' => $validated['due_date'] ?? null,
            'progress' => $validated['progress'] ?? 0,
            'recurrence_rule' => $validated['recurrence_rule'] ?? null,
            'created_by' => $project->client->team->owner_id,
            'position' => Task::where('project_id', $project->id)->max('position') + 1,
        ]);

        $task->load(['assignee', 'collaboratorAssignee', 'project']);

        return response()->json([
            'task' => $this->formatTask($task),
        ], 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if (!$this->taskBelongsToClient($task, $client->id)) {
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
            'assigned_to' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'recurrence_rule' => ['nullable', 'string', 'in:daily,weekly,monthly'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);

        // Handle assigned_to
        if (array_key_exists('assigned_to', $validated)) {
            $assigneeData = $this->resolveAssignee($validated['assigned_to'], $client->id);
            $validated['assigned_to'] = $assigneeData['assigned_to'];
            $validated['assignee_type'] = $assigneeData['assignee_type'];
        }

        $task->update($validated);
        $task->load(['assignee', 'collaboratorAssignee', 'project']);

        return response()->json([
            'task' => $this->formatTask($task),
        ]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if (!$this->taskBelongsToClient($task, $client->id)) {
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

    protected function resolveAssignee(?int $assignedTo, int $clientId): array
    {
        if (!$assignedTo) {
            return ['assigned_to' => null, 'assignee_type' => null];
        }

        // Check if it's a collaborator of this client
        $collaborator = ClientCollaborator::where('id', $assignedTo)
            ->where('client_id', $clientId)
            ->first();

        if ($collaborator) {
            return ['assigned_to' => $assignedTo, 'assignee_type' => 'collaborator'];
        }

        // Otherwise assume it's a user (team member)
        return ['assigned_to' => $assignedTo, 'assignee_type' => 'user'];
    }

    protected function hasPermission($collaborator, string $permission): bool
    {
        return $collaborator->hasPermission($permission);
    }

    protected function taskBelongsToClient(Task $task, int $clientId): bool
    {
        if ($task->client_id === $clientId) {
            return true;
        }

        if ($task->project && $task->project->client_id === $clientId) {
            return true;
        }

        return false;
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
            'assignee' => $task->assignee_info,
            'created_at' => $task->created_at,
            'updated_at' => $task->updated_at,
        ];
    }
}
