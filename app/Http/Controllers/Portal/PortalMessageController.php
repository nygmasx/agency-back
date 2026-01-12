<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalMessageController extends Controller
{
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $client = $request->client;

        if ($conversation->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'messages' => $messages->map(fn ($message) => $this->formatMessage($message)),
        ]);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if ($conversation->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $message = $conversation->messages()->create([
            'author_type' => 'collaborator',
            'author_id' => $collaborator->id,
            'content' => $validated['content'],
        ]);

        $conversation->touch();

        return response()->json([
            'message' => $this->formatMessage($message),
        ], 201);
    }

    protected function formatMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'content' => $message->content,
            'author' => $message->author,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
        ];
    }
}
