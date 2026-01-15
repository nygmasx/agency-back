<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\PortalLoginCodeMail;
use App\Models\ClientCollaborator;
use App\Models\PortalLoginCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
                'role' => $collaborator->role->value,
                'can_view' => $collaborator->canView(),
                'can_comment' => $collaborator->canComment(),
                'can_edit' => $collaborator->canEdit(),
                'expires_at' => $collaborator->expires_at,
                'created_at' => $collaborator->created_at,
            ],
            'client' => [
                'id' => $collaborator->client->id,
                'name' => $collaborator->client->name,
                'company' => $collaborator->client->company,
            ],
            'token' => $collaborator->token,
        ]);
    }

    public function requestCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($request->email);

        $collaborator = ClientCollaborator::with('client')
            ->where('email', $email)
            ->where('access_type', 'email')
            ->first();

        if (!$collaborator) {
            // Return success even if email doesn't exist (security)
            return response()->json([
                'message' => 'Si cette adresse est enregistrée, un code vous sera envoyé.',
            ]);
        }

        if ($collaborator->isExpired()) {
            return response()->json([
                'message' => 'Votre accès au portail a expiré.',
            ], 401);
        }

        // Check rate limit (max 5 codes per hour)
        if (PortalLoginCode::countRecentAttempts($email) >= 5) {
            return response()->json([
                'message' => 'Trop de tentatives. Veuillez réessayer dans une heure.',
            ], 429);
        }

        $loginCode = PortalLoginCode::createForEmail($email);

        Mail::to($email)->send(new PortalLoginCodeMail(
            code: $loginCode->code,
            clientName: $collaborator->client->name,
        ));

        return response()->json([
            'message' => 'Si cette adresse est enregistrée, un code vous sera envoyé.',
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $email = strtolower($request->email);

        $loginCode = PortalLoginCode::verify($email, $request->code);

        if (!$loginCode) {
            return response()->json([
                'message' => 'Code invalide ou expiré.',
            ], 401);
        }

        $collaborator = ClientCollaborator::with('client')
            ->where('email', $email)
            ->where('access_type', 'email')
            ->first();

        if (!$collaborator) {
            return response()->json([
                'message' => 'Collaborateur non trouvé.',
            ], 404);
        }

        if ($collaborator->isExpired()) {
            return response()->json([
                'message' => 'Votre accès au portail a expiré.',
            ], 401);
        }

        // Delete the used code
        $loginCode->delete();

        return response()->json([
            'collaborator' => [
                'id' => $collaborator->id,
                'name' => $collaborator->name,
                'email' => $collaborator->email,
                'role' => $collaborator->role->value,
                'can_view' => $collaborator->canView(),
                'can_comment' => $collaborator->canComment(),
                'can_edit' => $collaborator->canEdit(),
                'expires_at' => $collaborator->expires_at,
                'created_at' => $collaborator->created_at,
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
