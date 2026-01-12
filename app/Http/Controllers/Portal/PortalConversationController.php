<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = $request->client;

        $conversations = Conversation::where('client_id', $client->id)
            ->with('latestMessage')
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'conversations' => $conversations->map(fn ($conversation) => $this->formatConversation($conversation)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = Conversation::create([
            'client_id' => $client->id,
            'subject' => $validated['subject'],
        ]);

        $conversation->messages()->create([
            'author_type' => 'collaborator',
            'author_id' => $collaborator->id,
            'content' => $validated['message'],
        ]);

        $conversation->load('latestMessage');
        $conversation->loadCount('messages');

        return response()->json([
            'conversation' => $this->formatConversation($conversation),
        ], 201);
    }

    protected function formatConversation(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'subject' => $conversation->subject,
            'messages_count' => $conversation->messages_count ?? 0,
            'latest_message' => $conversation->latestMessage ? [
                'id' => $conversation->latestMessage->id,
                'content' => $conversation->latestMessage->content,
                'author' => $conversation->latestMessage->author,
                'created_at' => $conversation->latestMessage->created_at,
            ] : null,
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at,
        ];
    }
}
