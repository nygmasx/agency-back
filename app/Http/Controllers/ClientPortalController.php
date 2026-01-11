<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\ClientCollaborator;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    public function auth(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $collaborator = ClientCollaborator::with('client')
            ->where('token', $request->token)
            ->first();

        if (!$collaborator) {
            return response()->json([
                'message' => 'Invalid access token.',
            ], 401);
        }

        if ($collaborator->isExpired()) {
            return response()->json([
                'message' => 'Access token has expired.',
            ], 401);
        }

        return response()->json([
            'collaborator' => [
                'id' => $collaborator->id,
                'name' => $collaborator->name,
                'email' => $collaborator->email,
                'permissions' => $collaborator->permissions,
            ],
            'client' => [
                'id' => $collaborator->client->id,
                'name' => $collaborator->client->name,
                'company' => $collaborator->client->company,
            ],
            'token' => $collaborator->token,
        ]);
    }

    public function projects(Request $request): JsonResponse
    {
        $collaborator = $this->getCollaborator($request);

        if (!$collaborator) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $projects = $collaborator->client
            ->projects()
            ->where('status', '!=', 'archived')
            ->withCount(['tasks', 'pendingTasks', 'completedTasks'])
            ->get();

        return response()->json([
            'projects' => ProjectResource::collection($projects),
        ]);
    }

    public function projectTasks(Request $request, Project $project): JsonResponse
    {
        $collaborator = $this->getCollaborator($request);

        if (!$collaborator) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($project->client_id !== $collaborator->client_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tasks = $project->tasks()
            ->with(['assignee'])
            ->orderBy('position')
            ->get();

        return response()->json([
            'project' => new ProjectResource($project),
            'tasks' => TaskResource::collection($tasks),
        ]);
    }

    protected function getCollaborator(Request $request): ?ClientCollaborator
    {
        $token = $request->bearerToken() ?? $request->header('X-Portal-Token');

        if (!$token) {
            return null;
        }

        $collaborator = ClientCollaborator::where('token', $token)->first();

        if (!$collaborator || $collaborator->isExpired()) {
            return null;
        }

        return $collaborator;
    }
}
