<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\Task\ReorderTasksRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorize('view', $team);

        $query = Task::query()
            ->forTeam($team)
            ->with(['assignee', 'creator']);

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $tasks = $query->get();

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $role = $team->getMemberRole($request->user());
        if (!$role?->canCreateTasks()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task = Task::create([
            ...$request->validated(),
            'team_id' => $team->id,
            'created_by' => $request->user()->id,
        ]);

        $task->load(['assignee', 'creator']);

        return response()->json([
            'message' => 'Task created successfully.',
            'task' => new TaskResource($task),
        ], 201);
    }

    public function show(Request $request, Team $team, Task $task): TaskResource
    {
        $this->authorize('view', $team);

        if ($task->team_id !== $team->id) {
            abort(404);
        }

        $task->load(['assignee', 'creator', 'project']);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Team $team, Task $task): JsonResponse
    {
        $this->authorize('view', $team);

        if ($task->team_id !== $team->id) {
            abort(404);
        }

        $role = $team->getMemberRole($request->user());
        if (!$role?->canEditTasks()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task->update($request->validated());
        $task->load(['assignee', 'creator']);

        return response()->json([
            'message' => 'Task updated successfully.',
            'task' => new TaskResource($task),
        ]);
    }

    public function destroy(Request $request, Team $team, Task $task): JsonResponse
    {
        $this->authorize('view', $team);

        if ($task->team_id !== $team->id) {
            abort(404);
        }

        $role = $team->getMemberRole($request->user());
        if (!$role?->canDeleteTasks()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    public function updateProgress(Request $request, Team $team, Task $task): JsonResponse
    {
        $this->authorize('view', $team);

        if ($task->team_id !== $team->id) {
            abort(404);
        }

        $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $task->update([
            'progress' => $request->progress,
            'status' => $request->progress === 100 ? TaskStatus::Done : $task->status,
        ]);

        return response()->json([
            'message' => 'Progress updated successfully.',
            'task' => new TaskResource($task),
        ]);
    }

    public function reorder(ReorderTasksRequest $request, Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        foreach ($request->validated('tasks') as $taskData) {
            $task = Task::find($taskData['id']);

            if ($task && $task->team_id === $team->id) {
                $updateData = ['position' => $taskData['position']];

                if (isset($taskData['status'])) {
                    $updateData['status'] = $taskData['status'];
                }

                $task->update($updateData);
            }
        }

        return response()->json([
            'message' => 'Tasks reordered successfully.',
        ]);
    }

    public function projectTasks(Request $request, Project $project): AnonymousResourceCollection
    {
        $team = $project->client->team;
        $this->authorize('view', $team);

        $query = Task::query()
            ->forProject($project)
            ->with(['assignee', 'creator']);

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        return TaskResource::collection($query->get());
    }

    public function storeProjectTask(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $team = $project->client->team;
        $this->authorize('view', $team);

        $role = $team->getMemberRole($request->user());
        if (!$role?->canCreateTasks()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task = Task::create([
            ...$request->validated(),
            'team_id' => $team->id,
            'project_id' => $project->id,
            'created_by' => $request->user()->id,
        ]);

        $task->load(['assignee', 'creator']);

        return response()->json([
            'message' => 'Task created successfully.',
            'task' => new TaskResource($task),
        ], 201);
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($request->has('status')) {
            $statuses = explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        if ($request->has('priority')) {
            $priorities = explode(',', $request->priority);
            $query->whereIn('priority', $priorities);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('due_from') && $request->has('due_to')) {
            $query->dueBetween($request->due_from, $request->due_to);
        }

        if ($request->boolean('overdue')) {
            $query->overdue();
        }
    }

    protected function applySorting($query, Request $request): void
    {
        $view = $request->get('view', 'list');

        switch ($view) {
            case 'kanban':
                $query->orderBy('status')->orderBy('position');
                break;
            case 'calendar':
                $query->orderBy('due_date')->orderBy('priority', 'desc');
                break;
            default:
                $sortBy = $request->get('sort_by', 'created_at');
                $sortDir = $request->get('sort_dir', 'desc');
                $query->orderBy($sortBy, $sortDir);
        }
    }
}
