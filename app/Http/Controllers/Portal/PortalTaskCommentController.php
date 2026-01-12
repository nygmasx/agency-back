<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalTaskCommentController extends Controller
{
    public function index(Request $request, Task $task): JsonResponse
    {
        $client = $request->client;

        if (!$task->project || $task->project->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comments = $task->comments()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'comments' => $comments->map(fn ($comment) => $this->formatComment($comment)),
        ]);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if (!$task->project || $task->project->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$this->hasPermission($collaborator, 'comment')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'collaborator_id' => $collaborator->id,
            'content' => $validated['content'],
        ]);

        return response()->json([
            'comment' => $this->formatComment($comment),
        ], 201);
    }

    protected function hasPermission($collaborator, string $permission): bool
    {
        $permissions = $collaborator->permissions ?? ['view'];
        return in_array($permission, $permissions);
    }

    protected function formatComment(TaskComment $comment): array
    {
        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'author' => $comment->author,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
        ];
    }
}
