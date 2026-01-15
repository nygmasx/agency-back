<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function index(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        // Check if user belongs to the task's team
        if ($task->team_id && !$user->teams()->where('teams.id', $task->team_id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comments = $task->comments()
            ->with(['user', 'collaborator'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'comments' => $comments->map(fn ($comment) => $this->formatComment($comment)),
        ]);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        // Check if user belongs to the task's team
        if ($task->team_id && !$user->teams()->where('teams.id', $task->team_id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => $validated['content'],
        ]);

        $comment->load('user');

        return response()->json([
            'comment' => $this->formatComment($comment),
        ], 201);
    }

    public function destroy(Request $request, Task $task, TaskComment $comment): JsonResponse
    {
        $user = $request->user();

        // Check if user belongs to the task's team
        if ($task->team_id && !$user->teams()->where('teams.id', $task->team_id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($comment->task_id !== $task->id) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        // Only allow deleting own comments (or team owner can delete any)
        $team = $task->team;
        $isOwner = $team && $team->owner_id === $user->id;

        if ($comment->user_id !== $user->id && !$isOwner) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.',
        ]);
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
