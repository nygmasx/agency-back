<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Http\Requests\Team\UpdateMemberRoleRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamMemberController extends Controller
{
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorize('view', $team);

        $members = $team->teamMembers()
            ->with('user')
            ->get();

        return TeamMemberResource::collection($members);
    }

    public function update(UpdateMemberRoleRequest $request, Team $team, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $team);

        $member = $team->teamMembers()->where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'User is not a member of this team.',
            ], 404);
        }

        if ($member->role === TeamRole::Owner) {
            return response()->json([
                'message' => 'Cannot change the role of the team owner.',
            ], 403);
        }

        $member->update([
            'role' => $request->validated('role'),
        ]);

        $member->load('user');

        return response()->json([
            'message' => 'Member role updated successfully.',
            'member' => new TeamMemberResource($member),
        ]);
    }

    public function destroy(Request $request, Team $team, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $team);

        $member = $team->teamMembers()->where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'User is not a member of this team.',
            ], 404);
        }

        if ($member->role === TeamRole::Owner) {
            return response()->json([
                'message' => 'Cannot remove the team owner.',
            ], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Cannot remove yourself from the team.',
            ], 403);
        }

        $member->delete();

        return response()->json([
            'message' => 'Member removed successfully.',
        ]);
    }

    public function leave(Request $request, Team $team): JsonResponse
    {
        $member = $team->teamMembers()->where('user_id', $request->user()->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'You are not a member of this team.',
            ], 404);
        }

        if ($member->role === TeamRole::Owner) {
            return response()->json([
                'message' => 'Owner cannot leave the team. Transfer ownership or delete the team.',
            ], 403);
        }

        $member->delete();

        return response()->json([
            'message' => 'You have left the team.',
        ]);
    }
}
