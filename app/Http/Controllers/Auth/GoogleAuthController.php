<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectUrl(): JsonResponse
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    public function callback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid Google authentication code.',
            ], 401);
        }

        $existingUser = User::where('email', $googleUser->getEmail())->first();
        $isNewUser = !$existingUser;

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]
        );

        if ($isNewUser) {
            $this->createDefaultTeam($user);
        }

        $user->tokens()->delete();

        $token = $user->createToken('google_auth_token')->plainTextToken;
        $team = $user->ownedTeams()->first();

        return response()->json([
            'user' => $user,
            'team' => $team ? new TeamResource($team) : null,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function callbackWithToken(Request $request): JsonResponse
    {
        $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->access_token);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid Google access token.',
            ], 401);
        }

        $existingUser = User::where('email', $googleUser->getEmail())->first();
        $isNewUser = !$existingUser;

        if ($existingUser) {
            $existingUser->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'name' => $existingUser->name ?? $googleUser->getName(),
            ]);
            $user = $existingUser;
        } else {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
            $this->createDefaultTeam($user);
        }

        $user->tokens()->delete();

        $token = $user->createToken('google_auth_token')->plainTextToken;
        $team = $user->ownedTeams()->first();

        return response()->json([
            'user' => $user,
            'team' => $team ? new TeamResource($team) : null,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    protected function createDefaultTeam(User $user): Team
    {
        $team = Team::create([
            'name' => $user->name,
            'owner_id' => $user->id,
        ]);

        $team->members()->attach($user->id, [
            'role' => TeamRole::Owner->value,
        ]);

        return $team;
    }
}
