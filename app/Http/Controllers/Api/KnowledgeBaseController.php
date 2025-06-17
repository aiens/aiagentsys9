<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KnowledgeBaseService;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class KnowledgeBaseController extends Controller
{
    protected KnowledgeBaseService $knowledgeBaseService;

    public function __construct(KnowledgeBaseService $knowledgeBaseService)
    {
        $this->knowledgeBaseService = $knowledgeBaseService;
    }

    /**
     * Get user's knowledge bases.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->query('per_page', 20);

            $knowledgeBases = KnowledgeBase::where('user_id', $user->id)
                ->orWhere('is_public', true)
                ->active()
                ->orderBy('last_updated_at', 'desc')
                ->paginate($perPage);

            return $this->paginated($knowledgeBases->through(function ($kb) {
                return [
                    'id' => $kb->id,
                    'name' => $kb->name,
                    'description' => $kb->description,
                    'vector_db_type' => $kb->vector_db_type,
                    'embedding_model' => $kb->embedding_model,
                    'document_count' => $kb->document_count,
                    'chunk_count' => $kb->chunk_count,
                    'total_tokens' => $kb->total_tokens,
                    'is_public' => $kb->is_public,
                    'is_owner' => $kb->user_id === auth()->id(),
                    'last_updated_at' => $kb->last_updated_at,
                    'created_at' => $kb->created_at,
                ];
            }));

        } catch (\Exception $e) {
            return $this->error('Failed to fetch knowledge bases: ' . $e->getMessage());
        }
    }

    /**
     * Create a new knowledge base.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'vector_db_type' => 'sometimes|in:pinecone,weaviate,qdrant,elasticsearch',
            'embedding_model' => 'sometimes|string',
            'chunk_size' => 'sometimes|integer|min:100|max:5000',
            'chunk_overlap' => 'sometimes|integer|min:0|max:1000',
            'is_public' => 'sometimes|boolean',
            'settings' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $request->user();
            $knowledgeBase = $this->knowledgeBaseService->createKnowledgeBase($user, $request->all());

            return $this->created([
                'id' => $knowledgeBase->id,
                'name' => $knowledgeBase->name,
                'description' => $knowledgeBase->description,
                'vector_db_type' => $knowledgeBase->vector_db_type,
                'embedding_model' => $knowledgeBase->embedding_model,
                'chunk_size' => $knowledgeBase->chunk_size,
                'chunk_overlap' => $knowledgeBase->chunk_overlap,
                'is_public' => $knowledgeBase->is_public,
                'created_at' => $knowledgeBase->created_at,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to create knowledge base: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific knowledge base.
     */
    public function show(Request $request, int $knowledgeBaseId): JsonResponse
    {
        try {
            $user = $request->user();
            $knowledgeBase = KnowledgeBase::findOrFail($knowledgeBaseId);

            if (!$knowledgeBase->isAccessibleBy($user)) {
                return $this->forbidden('You do not have access to this knowledge base');
            }

            return $this->success([
                'id' => $knowledgeBase->id,
                'name' => $knowledgeBase->name,
                'description' => $knowledgeBase->description,
                'vector_db_type' => $knowledgeBase->vector_db_type,
                'vector_db_index' => $knowledgeBase->vector_db_index,
                'embedding_model' => $knowledgeBase->embedding_model,
                'chunk_size' => $knowledgeBase->chunk_size,
                'chunk_overlap' => $knowledgeBase->chunk_overlap,
                'settings' => $knowledgeBase->getSettingsWithDefaults(),
                'document_count' => $knowledgeBase->document_count,
                'chunk_count' => $knowledgeBase->chunk_count,
                'total_tokens' => $knowledgeBase->total_tokens,
                'is_public' => $knowledgeBase->is_public,
                'is_active' => $knowledgeBase->is_active,
                'is_owner' => $knowledgeBase->user_id === $user->id,
                'last_updated_at' => $knowledgeBase->last_updated_at,
                'created_at' => $knowledgeBase->created_at,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch knowledge base: ' . $e->getMessage());
        }
    }

    /**
     * Update a knowledge base.
     */
    public function update(Request $request, int $knowledgeBaseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'is_public' => 'sometimes|boolean',
            'settings' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $request->user();
            $knowledgeBase = KnowledgeBase::where('user_id', $user->id)
                ->findOrFail($knowledgeBaseId);

            $knowledgeBase->update($request->only(['name', 'description', 'is_public', 'settings']));

            return $this->success([
                'id' => $knowledgeBase->id,
                'name' => $knowledgeBase->name,
                'description' => $knowledgeBase->description,
                'is_public' => $knowledgeBase->is_public,
                'settings' => $knowledgeBase->settings,
                'updated_at' => $knowledgeBase->updated_at,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to update knowledge base: ' . $e->getMessage());
        }
    }

    /**
     * Delete a knowledge base.
     */
    public function destroy(Request $request, int $knowledgeBaseId): JsonResponse
    {
        try {
            $user = $request->user();
            $knowledgeBase = KnowledgeBase::where('user_id', $user->id)
                ->findOrFail($knowledgeBaseId);

            $knowledgeBase->delete();

            return $this->noContent();

        } catch (\Exception $e) {
            return $this->error('Failed to delete knowledge base: ' . $e->getMessage());
        }
    }

    /**
     * Upload a document to a knowledge base.
     */
    public function uploadDocument(Request $request, int $knowledgeBaseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200', // 50MB max
            'title' => 'sometimes|string|max:255',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $request->user();
            $knowledgeBase = KnowledgeBase::where('user_id', $user->id)
                ->findOrFail($knowledgeBaseId);

            $metadata = $request->metadata ?? [];
            if ($request->title) {
                $metadata['title'] = $request->title;
            }

            $document = $this->knowledgeBaseService->uploadDocument(
                $knowledgeBase,
                $user,
                $request->file('file'),
                $metadata
            );

            return $this->created([
                'id' => $document->id,
                'title' => $document->title,
                'filename' => $document->filename,
                'file_type' => $document->file_type,
                'file_size' => $document->file_size,
                'status' => $document->status,
                'created_at' => $document->created_at,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to upload document: ' . $e->getMessage());
        }
    }

    /**
     * Get documents in a knowledge base.
     */
    public function documents(Request $request, int $knowledgeBaseId): JsonResponse
    {
        try {
            $user = $request->user();
            $knowledgeBase = KnowledgeBase::findOrFail($knowledgeBaseId);

            if (!$knowledgeBase->isAccessibleBy($user)) {
                return $this->forbidden('You do not have access to this knowledge base');
            }

            $perPage = $request->query('per_page', 20);
            $status = $request->query('status');

            $documents = KnowledgeDocument::where('knowledge_base_id', $knowledgeBaseId)
                ->when($status, fn($q) => $q->withStatus($status))
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->paginated($documents->through(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'filename' => $document->filename,
                    'file_type' => $document->file_type,
                    'file_size' => $document->file_size,
                    'file_size_human' => $document->getHumanReadableSize(),
                    'status' => $document->status,
                    'chunk_count' => $document->chunk_count,
                    'token_count' => $document->token_count,
                    'error_message' => $document->error_message,
                    'processed_at' => $document->processed_at,
                    'created_at' => $document->created_at,
                ];
            }));

        } catch (\Exception $e) {
            return $this->error('Failed to fetch documents: ' . $e->getMessage());
        }
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(Request $request, int $knowledgeBaseId, int $documentId): JsonResponse
    {
        try {
            $user = $request->user();
            $document = KnowledgeDocument::where('knowledge_base_id', $knowledgeBaseId)
                ->where('user_id', $user->id)
                ->findOrFail($documentId);

            $this->knowledgeBaseService->deleteDocument($document);

            return $this->noContent();

        } catch (\Exception $e) {
            return $this->error('Failed to delete document: ' . $e->getMessage());
        }
    }

    /**
     * Search a knowledge base.
     */
    public function search(Request $request, int $knowledgeBaseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:1000',
            'top_k' => 'sometimes|integer|min:1|max:50',
            'similarity_threshold' => 'sometimes|numeric|between:0,1',
            'strategy' => 'sometimes|in:semantic,keyword,hybrid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $request->user();
            $knowledgeBase = KnowledgeBase::findOrFail($knowledgeBaseId);

            if (!$knowledgeBase->isAccessibleBy($user)) {
                return $this->forbidden('You do not have access to this knowledge base');
            }

            $options = $request->only(['top_k', 'similarity_threshold', 'strategy']);
            $results = $this->knowledgeBaseService->search($knowledgeBase, $request->query, $options);

            return $this->success($results);

        } catch (\Exception $e) {
            return $this->error('Failed to search knowledge base: ' . $e->getMessage());
        }
    }

    /**
     * Get knowledge base statistics.
     */
    public function statistics(Request $request, int $knowledgeBaseId): JsonResponse
    {
        try {
            $user = $request->user();
            $knowledgeBase = KnowledgeBase::findOrFail($knowledgeBaseId);

            if (!$knowledgeBase->isAccessibleBy($user)) {
                return $this->forbidden('You do not have access to this knowledge base');
            }

            $stats = $this->knowledgeBaseService->getStatistics($knowledgeBase);

            return $this->success($stats);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch statistics: ' . $e->getMessage());
        }
    }
}
