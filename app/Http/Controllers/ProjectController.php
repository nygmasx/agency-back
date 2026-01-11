<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Resources\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request, Client $client): AnonymousResourceCollection
    {
        $team = $client->team;
        $this->authorize('view', $team);

        $projects = $client->projects()
            ->withCount(['tasks', 'pendingTasks', 'completedTasks'])
            ->latest()
            ->get();

        return ProjectResource::collection($projects);
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
        ]);

        $project = $client->projects()->create($validated);

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => new ProjectResource($project),
        ], 201);
    }

    public function show(Request $request, Client $client, Project $project): ProjectResource
    {
        $team = $client->team;
        $this->authorize('view', $team);

        if ($project->client_id !== $client->id) {
            abort(404);
        }

        $project->loadCount(['tasks', 'pendingTasks', 'completedTasks']);
        $project->load('tasks');

        return new ProjectResource($project);
    }

    public function update(Request $request, Client $client, Project $project): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        if ($project->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Project updated successfully.',
            'project' => new ProjectResource($project),
        ]);
    }

    public function destroy(Request $request, Client $client, Project $project): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        if ($project->client_id !== $client->id) {
            abort(404);
        }

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }
}
