<?php

namespace App\Http\Controllers;

use App\Mail\PortalInvitationMail;
use App\Models\Client;
use App\Models\ClientCollaborator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ClientCollaboratorController extends Controller
{
    public function index(Request $request, Client $client)
    {
        $team = $client->team;
        $this->authorize('view', $team);

        $collaborators = $client->collaborators()->get();

        return response()->json([
            'collaborators' => $collaborators->map(fn ($c) => $this->formatCollaborator($c)),
        ]);
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['nullable', 'string', 'in:viewer,commenter,editor'],
            'access_type' => ['nullable', 'string', 'in:link,email'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $accessType = $validated['access_type'] ?? 'email';
        $role = $validated['role'] ?? 'viewer';

        $collaborator = $client->collaborators()->create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role' => $role,
            'access_type' => $accessType,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        $response = [
            'message' => 'Collaborator added successfully.',
            'collaborator' => $this->formatCollaborator($collaborator),
        ];

        // Handle based on access type
        if ($accessType === 'link') {
            $portalUrl = config('app.frontend_url') . '/portal/' . $collaborator->token;
            $response['collaborator']['token'] = $collaborator->token;
            $response['collaborator']['portal_url'] = $portalUrl;

            Mail::to($collaborator->email)->send(new PortalInvitationMail(
                collaborator: $collaborator,
                client: $client,
                portalUrl: $portalUrl,
            ));
        } else {
            Mail::to($collaborator->email)->send(new PortalInvitationMail(
                collaborator: $collaborator,
                client: $client,
            ));
        }

        return response()->json($response, 201);
    }

    public function update(Request $request, Client $client, ClientCollaborator $collaborator): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        if ($collaborator->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', 'string', 'in:viewer,commenter,editor'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $collaborator->update($validated);

        return response()->json([
            'message' => 'Collaborator updated successfully.',
            'collaborator' => $this->formatCollaborator($collaborator),
        ]);
    }

    public function resendInvitation(Request $request, Client $client, ClientCollaborator $collaborator): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        if ($collaborator->client_id !== $client->id) {
            abort(404);
        }

        if ($collaborator->access_type === 'link') {
            $portalUrl = config('app.frontend_url') . '/portal/' . $collaborator->token;
            Mail::to($collaborator->email)->send(new PortalInvitationMail(
                collaborator: $collaborator,
                client: $client,
                portalUrl: $portalUrl,
            ));
        } else {
            Mail::to($collaborator->email)->send(new PortalInvitationMail(
                collaborator: $collaborator,
                client: $client,
            ));
        }

        return response()->json([
            'message' => 'Invitation resent successfully.',
        ]);
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

    protected function formatCollaborator(ClientCollaborator $collaborator): array
    {
        return [
            'id' => $collaborator->id,
            'name' => $collaborator->name,
            'email' => $collaborator->email,
            'role' => $collaborator->role->value,
            'access_type' => $collaborator->access_type,
            'can_view' => $collaborator->canView(),
            'can_comment' => $collaborator->canComment(),
            'can_edit' => $collaborator->canEdit(),
            'expires_at' => $collaborator->expires_at,
            'is_expired' => $collaborator->isExpired(),
            'created_at' => $collaborator->created_at,
        ];
    }
}
