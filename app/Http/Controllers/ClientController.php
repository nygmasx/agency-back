<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorize('view', $team);

        $clients = $team->clients()
            ->withCount(['projects', 'activeProjects'])
            ->latest()
            ->get();

        return ClientResource::collection($clients);
    }

    public function store(Request $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
        ]);

        $client = $team->clients()->create($validated);

        return response()->json([
            'message' => 'Client created successfully.',
            'client' => new ClientResource($client),
        ], 201);
    }

    public function show(Request $request, Team $team, Client $client): ClientResource
    {
        $this->authorize('view', $team);

        if ($client->team_id !== $team->id) {
            abort(404);
        }

        $client->loadCount(['projects', 'activeProjects']);

        return new ClientResource($client);
    }

    public function update(Request $request, Team $team, Client $client): JsonResponse
    {
        $this->authorize('update', $team);

        if ($client->team_id !== $team->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
        ]);

        $client->update($validated);

        return response()->json([
            'message' => 'Client updated successfully.',
            'client' => new ClientResource($client),
        ]);
    }

    public function destroy(Request $request, Team $team, Client $client): JsonResponse
    {
        $this->authorize('update', $team);

        if ($client->team_id !== $team->id) {
            abort(404);
        }

        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully.',
        ]);
    }
}
