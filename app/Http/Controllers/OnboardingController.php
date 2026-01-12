<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function checkSubdomain(string $subdomain): JsonResponse
    {
        if (!preg_match('/^[a-z0-9-]{3,}$/', $subdomain)) {
            return response()->json(['available' => false]);
        }

        $exists = Team::where('subdomain', $subdomain)->exists();

        return response()->json(['available' => !$exists]);
    }

    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subdomain' => [
                'required',
                'string',
                'min:3',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('teams', 'subdomain'),
            ],
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'business_type' => ['required', 'string', 'in:marketing,web_development,design,consulting,video_photo,seo_sea,social_media,other'],
            'client_count' => ['required', 'string', 'in:1,2,3,4,5,6+'],
        ]);

        $user = Auth::user();
        $team = $user->ownedTeams()->first();

        if (!$team) {
            return response()->json(['message' => 'No team found'], 404);
        }

        if ($team->onboarding_completed) {
            return response()->json(['message' => 'Onboarding already completed'], 400);
        }

        if ($user->name !== $validated['full_name']) {
            $user->update(['name' => $validated['full_name']]);
        }

        $team->update([
            'subdomain' => $validated['subdomain'],
            'business_type' => $validated['business_type'],
            'client_count' => $validated['client_count'],
            'onboarding_completed' => true,
        ]);

        return response()->json([
            'message' => 'Onboarding completed successfully',
            'team' => new TeamResource($team->fresh()),
        ]);
    }
}
