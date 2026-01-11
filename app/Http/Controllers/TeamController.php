<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamController extends Controller
{
    public function myTeam(Request $request): JsonResponse
    {
        $team = $request->user()
            ->ownedTeams()
            ->withCount('members')
            ->with('owner')
            ->first();

        if (!$team) {
            return response()->json([
                'message' => 'No team found.',
            ], 404);
        }

        return response()->json([
            'team' => new TeamResource($team),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $teams = $request->user()
            ->teams()
            ->withCount('members')
            ->with('owner')
            ->latest()
            ->get();

        return TeamResource::collection($teams);
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = Team::create([
            'name' => $request->validated('name'),
            'owner_id' => $request->user()->id,
        ]);

        $team->members()->attach($request->user()->id, [
            'role' => TeamRole::Owner->value,
        ]);

        $team->loadCount('members')->load('owner');

        return response()->json([
            'message' => 'Team created successfully.',
            'team' => new TeamResource($team),
        ], 201);
    }

    public function show(Request $request, Team $team): TeamResource
    {
        $this->authorize('view', $team);

        $team->loadCount('members')->load('owner');

        return new TeamResource($team);
    }

    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $team->update($request->validated());

        $team->loadCount('members')->load('owner');

        return response()->json([
            'message' => 'Team updated successfully.',
            'team' => new TeamResource($team),
        ]);
    }

    public function destroy(Request $request, Team $team): JsonResponse
    {
        $this->authorize('delete', $team);

        $team->delete();

        return response()->json([
            'message' => 'Team deleted successfully.',
        ]);
    }
}
