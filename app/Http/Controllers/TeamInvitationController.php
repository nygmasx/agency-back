<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\InviteMemberRequest;
use App\Http\Resources\TeamInvitationResource;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamInvitationController extends Controller
{
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorize('manageMembers', $team);

        $invitations = $team->pendingInvitations()->latest()->get();

        return TeamInvitationResource::collection($invitations);
    }

    public function store(InviteMemberRequest $request, Team $team): JsonResponse
    {
        $this->authorize('manageMembers', $team);

        $email = $request->validated('email');

        if ($email) {
            $existingUser = User::where('email', $email)->first();
            if ($existingUser && $team->hasMember($existingUser)) {
                return response()->json([
                    'message' => 'This user is already a member of the team.',
                ], 422);
            }

            $existingInvitation = $team->pendingInvitations()
                ->where('email', $email)
                ->first();

            if ($existingInvitation) {
                return response()->json([
                    'message' => 'An invitation has already been sent to this email.',
                ], 422);
            }
        }

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => $email,
            'role' => $request->validated('role'),
        ]);

        return response()->json([
            'message' => $email
                ? 'Invitation sent successfully.'
                : 'Invite link created successfully.',
            'invitation' => new TeamInvitationResource($invitation),
        ], 201);
    }

    public function destroy(Request $request, Team $team, TeamInvitation $invitation): JsonResponse
    {
        $this->authorize('manageMembers', $team);

        if ($invitation->team_id !== $team->id) {
            return response()->json([
                'message' => 'Invitation not found.',
            ], 404);
        }

        $invitation->delete();

        return response()->json([
            'message' => 'Invitation cancelled.',
        ]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = TeamInvitation::where('token', $token)->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invalid invitation.',
            ], 404);
        }

        if ($invitation->isExpired()) {
            return response()->json([
                'message' => 'This invitation has expired.',
            ], 410);
        }

        if ($invitation->isAccepted()) {
            return response()->json([
                'message' => 'This invitation has already been used.',
            ], 410);
        }

        $user = $request->user();

        if ($invitation->email && $invitation->email !== $user->email) {
            return response()->json([
                'message' => 'This invitation was sent to a different email address.',
            ], 403);
        }

        if ($invitation->team->hasMember($user)) {
            return response()->json([
                'message' => 'You are already a member of this team.',
            ], 422);
        }

        $member = $invitation->accept($user);

        return response()->json([
            'message' => 'You have joined the team.',
            'team' => [
                'id' => $invitation->team->id,
                'name' => $invitation->team->name,
                'slug' => $invitation->team->slug,
            ],
        ]);
    }

    public function show(string $token): JsonResponse
    {
        $invitation = TeamInvitation::with('team:id,name,slug')
            ->where('token', $token)
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invalid invitation.',
            ], 404);
        }

        if ($invitation->isExpired()) {
            return response()->json([
                'message' => 'This invitation has expired.',
            ], 410);
        }

        if ($invitation->isAccepted()) {
            return response()->json([
                'message' => 'This invitation has already been used.',
            ], 410);
        }

        return response()->json([
            'invitation' => [
                'team' => [
                    'name' => $invitation->team->name,
                ],
                'role' => $invitation->role->value,
                'expires_at' => $invitation->expires_at,
            ],
        ]);
    }
}
