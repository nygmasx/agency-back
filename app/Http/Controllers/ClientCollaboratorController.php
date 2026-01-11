<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientCollaborator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientCollaboratorController extends Controller
{
    public function index(Request $request, Client $client)
    {
        $team = $client->team;
        $this->authorize('view', $team);

        $collaborators = $client->collaborators()->get();

        return response()->json([
            'collaborators' => $collaborators->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'permissions' => $c->permissions,
                'expires_at' => $c->expires_at,
                'is_expired' => $c->isExpired(),
                'created_at' => $c->created_at,
            ]),
        ]);
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $collaborator = $client->collaborators()->create($validated);

        return response()->json([
            'message' => 'Collaborator added successfully.',
            'collaborator' => [
                'id' => $collaborator->id,
                'name' => $collaborator->name,
                'email' => $collaborator->email,
                'token' => $collaborator->token,
                'portal_url' => config('app.frontend_url') . '/portal/' . $collaborator->token,
                'permissions' => $collaborator->permissions,
                'expires_at' => $collaborator->expires_at,
            ],
        ], 201);
    }

    public function destroy(Request $request, Client $client, ClientCollaborator $collaborator): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        if ($collaborator->client_id !== $client->id) {
            abort(404);
        }

        $collaborator->delete();

        return response()->json([
            'message' => 'Collaborator removed successfully.',
        ]);
    }
}
