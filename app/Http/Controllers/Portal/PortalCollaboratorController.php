<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\PortalInvitationMail;
use App\Models\ClientCollaborator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PortalCollaboratorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = $request->client;

        $collaborators = $client->collaborators()
            ->orderBy('name')
            ->get();

        return response()->json([
            'collaborators' => $collaborators->map(fn ($c) => $this->formatCollaborator($c)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if (!$collaborator->hasPermission('edit')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:client_collaborators,email,NULL,id,client_id,' . $client->id],
            'role' => ['required', 'string', 'in:viewer,commenter,editor'],
        ]);

        $newCollaborator = $client->collaborators()->create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'access_type' => 'email',
        ]);

        // Send invitation email
        Mail::to($newCollaborator->email)->send(new PortalInvitationMail(
            collaborator: $newCollaborator,
            client: $client,
        ));

        return response()->json([
            'message' => 'Collaborator invited successfully.',
            'collaborator' => $this->formatCollaborator($newCollaborator),
        ], 201);
    }

    protected function formatCollaborator(ClientCollaborator $collaborator): array
    {
        return [
            'id' => $collaborator->id,
            'name' => $collaborator->name,
            'email' => $collaborator->email,
            'role' => $collaborator->role->value,
            'can_view' => $collaborator->canView(),
            'can_comment' => $collaborator->canComment(),
            'can_edit' => $collaborator->canEdit(),
            'expires_at' => $collaborator->expires_at,
            'created_at' => $collaborator->created_at,
        ];
    }
}
