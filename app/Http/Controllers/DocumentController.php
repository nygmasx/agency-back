<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request, Client $client): JsonResponse
    {
        $team = $client->team;
        $this->authorize('view', $team);

        $documents = Document::where('client_id', $client->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'documents' => $documents->map(fn ($doc) => $this->formatDocument($doc)),
        ]);
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $document = Document::create([
            'client_id' => $client->id,
            'team_id' => $team->id,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'created_by_id' => $request->user()->id,
            'created_by_type' => 'user',
        ]);

        return response()->json([
            'message' => 'Document created successfully.',
            'document' => $this->formatDocument($document),
        ], 201);
    }

    public function show(Request $request, Client $client, Document $document): JsonResponse
    {
        $team = $client->team;
        $this->authorize('view', $team);

        if ($document->client_id !== $client->id) {
            abort(404);
        }

        return response()->json([
            'document' => $this->formatDocument($document),
        ]);
    }

    public function update(Request $request, Client $client, Document $document): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        if ($document->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $document->update(array_merge($validated, [
            'updated_by_id' => $request->user()->id,
            'updated_by_type' => 'user',
        ]));

        return response()->json([
            'message' => 'Document updated successfully.',
            'document' => $this->formatDocument($document),
        ]);
    }

    public function destroy(Request $request, Client $client, Document $document): JsonResponse
    {
        $team = $client->team;
        $this->authorize('update', $team);

        if ($document->client_id !== $client->id) {
            abort(404);
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
            'client_id' => $document->client_id,
            'created_by' => $document->created_by,
            'updated_by' => $document->updated_by,
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
        ];
    }
}
