<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = $request->client;

        $documents = Document::where('client_id', $client->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'documents' => $documents->map(fn ($doc) => $this->formatDocument($doc)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if (!$collaborator->canEdit()) {
            return response()->json(['message' => 'Permission refusée'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $document = Document::create([
            'client_id' => $client->id,
            'team_id' => $client->team_id,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'created_by_id' => $collaborator->id,
            'created_by_type' => 'collaborator',
        ]);

        return response()->json([
            'document' => $this->formatDocument($document),
        ], 201);
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        $client = $request->client;

        if ($document->client_id !== $client->id) {
            return response()->json(['message' => 'Document introuvable'], 404);
        }

        return response()->json([
            'document' => $this->formatDocument($document),
        ]);
    }

    public function update(Request $request, Document $document): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if ($document->client_id !== $client->id) {
            return response()->json(['message' => 'Document introuvable'], 404);
        }

        if (!$collaborator->canEdit()) {
            return response()->json(['message' => 'Permission refusée'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $document->update(array_merge($validated, [
            'updated_by_id' => $collaborator->id,
            'updated_by_type' => 'collaborator',
        ]));

        return response()->json([
            'document' => $this->formatDocument($document),
        ]);
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        $client = $request->client;
        $collaborator = $request->collaborator;

        if ($document->client_id !== $client->id) {
            return response()->json(['message' => 'Document introuvable'], 404);
        }

        if (!$collaborator->canEdit()) {
            return response()->json(['message' => 'Permission refusée'], 403);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully.',
        ]);
    }

    protected function formatDocument(Document $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'content' => $document->content,
            'created_by' => $document->created_by,
            'updated_by' => $document->updated_by,
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
        ];
    }
}
