<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortalTaskFileController extends Controller
{
    public function index(Request $request, Task $task): JsonResponse
    {
        $client = $request->client;

        if (!$task->project || $task->project->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $files = $task->files()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'files' => $files->map(fn ($file) => $this->formatFile($file)),
        ]);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if (!$task->project || $task->project->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$this->hasPermission($collaborator, 'edit')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('task-files', 'public');

        $file = TaskFile::create([
            'task_id' => $task->id,
            'name' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'size' => $uploadedFile->getSize(),
            'mime_type' => $uploadedFile->getMimeType(),
            'uploaded_by_type' => 'collaborator',
            'uploaded_by_id' => $collaborator->id,
        ]);

        return response()->json([
            'file' => $this->formatFile($file),
        ], 201);
    }

    public function destroy(Request $request, Task $task, TaskFile $file): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if (!$task->project || $task->project->client_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($file->task_id !== $task->id) {
            return response()->json(['message' => 'File not found'], 404);
        }

        if (!$this->hasPermission($collaborator, 'edit')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return response()->json([
            'message' => 'File deleted successfully.',
        ]);
    }

    protected function hasPermission($collaborator, string $permission): bool
    {
        $permissions = $collaborator->permissions ?? ['view'];
        return in_array($permission, $permissions);
    }

    protected function formatFile(TaskFile $file): array
    {
        $uploader = $file->uploader;

        return [
            'id' => $file->id,
            'name' => $file->name,
            'size' => $file->size,
            'mime_type' => $file->mime_type,
            'url' => $file->url,
            'uploaded_by' => $uploader ? [
                'id' => $uploader->id,
                'name' => $uploader->name,
                'type' => $file->uploaded_by_type,
            ] : null,
            'created_at' => $file->created_at,
        ];
    }
}
