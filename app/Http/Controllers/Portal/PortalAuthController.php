<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientCollaborator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalAuthController extends Controller
{
    public function authenticate(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $collaborator = ClientCollaborator::with('client')
            ->where('token', $request->token)
            ->first();

        if (!$collaborator) {
            return response()->json([
                'message' => 'Invalid portal token.',
            ], 401);
        }

        if ($collaborator->isExpired()) {
            return response()->json([
                'message' => 'Portal access has expired.',
            ], 401);
        }

        return response()->json([
            'collaborator' => [
                'id' => $collaborator->id,
                'name' => $collaborator->name,
                'email' => $collaborator->email,
                'permissions' => $collaborator->permissions ?? ['view'],
            ],
            'client' => [
                'id' => $collaborator->client->id,
                'name' => $collaborator->client->name,
                'company' => $collaborator->client->company,
            ],
            'token' => $collaborator->token,
        ]);
    }
}
