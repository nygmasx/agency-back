<?php

namespace App\Http\Middleware;

use App\Models\ClientCollaborator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Portal-Token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Portal token required.',
            ], 401);
        }

        $collaborator = ClientCollaborator::with('client')
            ->where('token', $token)
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

        $request->merge([
            'collaborator' => $collaborator,
            'client' => $collaborator->client,
        ]);

        $request->setUserResolver(fn () => $collaborator);

        return $next($request);
    }
}
