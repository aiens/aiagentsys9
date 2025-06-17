<?php

namespace App\Services;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeChunk;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class KnowledgeBaseService
{
    protected EmbeddingService $embeddingService;
    protected VectorDatabaseService $vectorDbService;

    public function __construct(
        EmbeddingService $embeddingService,
        VectorDatabaseService $vectorDbService
    ) {
        $this->embeddingService = $embeddingService;
        $this->vectorDbService = $vectorDbService;
    }

    /**
     * Create a new knowledge base.
     */
    public function createKnowledgeBase(User $user, array $data): KnowledgeBase
    {
        $knowledgeBase = KnowledgeBase::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'vector_db_type' => $data['vector_db_type'] ?? config('knowledge.default_vector_db'),
            'embedding_model' => $data['embedding_model'] ?? config('knowledge.embedding.default_model'),
            'chunk_size' => $data['chunk_size'] ?? config('knowledge.document_processing.chunk_size'),
            'chunk_overlap' => $data['chunk_overlap'] ?? config('knowledge.document_processing.chunk_overlap'),
            'settings' => $data['settings'] ?? [],
            'is_public' => $data['is_public'] ?? false,
        ]);

        // Create vector database index
        $this->vectorDbService->createIndex($knowledgeBase);

        Log::info('Knowledge base created', [
            'knowledge_base_id' => $knowledgeBase->id,
            'user_id' => $user->id,
            'name' => $knowledgeBase->name,
        ]);

        return $knowledgeBase;
    }

    /**
     * Upload and process a document.
     */
    public function uploadDocument(
        KnowledgeBase $knowledgeBase,
        User $user,
        UploadedFile $file,
        array $metadata = []
    ): KnowledgeDocument {
        // Validate file
        $this->validateFile($file);

        // Generate file hash for deduplication
        $hash = hash_file('sha256', $file->getRealPath());

        // Check for duplicates
        $existingDocument = KnowledgeDocument::where('hash', $hash)
            ->where('knowledge_base_id', $knowledgeBase->id)
            ->first();

        if ($existingDocument) {
            throw new Exception('Document already exists in this knowledge base');
        }

        // Store file
        $filename = $this->generateUniqueFilename($file);
        $filePath = $file->storeAs('knowledge_bases/' . $knowledgeBase->id, $filename);

        // Create document record
        $document = KnowledgeDocument::create([
            'knowledge_base_id' => $knowledgeBase->id,
            'user_id' => $user->id,
            'title' => $metadata['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'filename' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'hash' => $hash,
            'metadata' => $metadata,
            'status' => 'pending',
        ]);

        // Queue document processing
        $this->queueDocumentProcessing($document);

        return $document;
    }

    /**
     * Process a document into chunks and embeddings.
     */
    public function processDocument(KnowledgeDocument $document): void
    {
        try {
            $document->markAsProcessing();

            // Parse document content
            $content = $this->parseDocument($document);
            $document->update(['content' => $content]);

            // Create chunks
            $chunks = $this->createChunks($document, $content);

            // Generate embeddings for chunks
            $this->generateEmbeddings($document, $chunks);

            // Update document status
            $document->markAsCompleted(count($chunks), $this->countTokens($content));

            // Update knowledge base statistics
            $document->knowledgeBase->updateCounts();

            Log::info('Document processed successfully', [
                'document_id' => $document->id,
                'chunks_created' => count($chunks),
            ]);

        } catch (Exception $e) {
            $document->markAsFailed($e->getMessage());

            Log::error('Document processing failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Search the knowledge base.
     */
    public function search(
        KnowledgeBase $knowledgeBase,
        string $query,
        array $options = []
    ): array {
        $topK = $options['top_k'] ?? config('knowledge.retrieval.default_top_k', 5);
        $similarityThreshold = $options['similarity_threshold'] ?? 
            config('knowledge.retrieval.similarity_threshold', 0.7);
        $strategy = $options['strategy'] ?? 'semantic';

        try {
            // Generate query embedding
            $queryEmbedding = $this->embeddingService->generateEmbedding(
                $query,
                $knowledgeBase->embedding_model
            );

            // Search vector database
            $results = $this->vectorDbService->search(
                $knowledgeBase,
                $queryEmbedding,
                $topK,
                $similarityThreshold
            );

            // Rerank results if enabled
            if (config('knowledge.retrieval.rerank_enabled')) {
                $results = $this->rerankResults($query, $results);
            }

            // Format results
            return $this->formatSearchResults($results);

        } catch (Exception $e) {
            Log::error('Knowledge base search failed', [
                'knowledge_base_id' => $knowledgeBase->id,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a document and its chunks.
     */
    public function deleteDocument(KnowledgeDocument $document): void
    {
        try {
            // Delete from vector database
            $chunks = $document->chunks;
            foreach ($chunks as $chunk) {
                if ($chunk->vector_id) {
                    $this->vectorDbService->deleteVector(
                        $document->knowledgeBase,
                        $chunk->vector_id
                    );
                }
            }

            // Delete chunks
            $document->chunks()->delete();

            // Delete file
            if (Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }

            // Delete document
            $document->delete();

            // Update knowledge base statistics
            $document->knowledgeBase->updateCounts();

            Log::info('Document deleted', [
                'document_id' => $document->id,
                'knowledge_base_id' => $document->knowledge_base_id,
            ]);

        } catch (Exception $e) {
            Log::error('Document deletion failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get knowledge base statistics.
     */
    public function getStatistics(KnowledgeBase $knowledgeBase): array
    {
        $documents = $knowledgeBase->documents();
        $chunks = $knowledgeBase->chunks();

        return [
            'total_documents' => $documents->count(),
            'processed_documents' => $documents->processed()->count(),
            'pending_documents' => $documents->pending()->count(),
            'failed_documents' => $documents->failed()->count(),
            'total_chunks' => $chunks->count(),
            'total_tokens' => $chunks->sum('token_count'),
            'total_cost' => $chunks->sum('embedding_cost'),
            'file_types' => $documents->selectRaw('file_type, COUNT(*) as count')
                ->groupBy('file_type')
                ->pluck('count', 'file_type')
                ->toArray(),
            'last_updated' => $knowledgeBase->last_updated_at,
        ];
    }

    /**
     * Validate uploaded file.
     */
    protected function validateFile(UploadedFile $file): void
    {
        $maxSize = config('knowledge.document_processing.max_file_size');
        $supportedFormats = config('knowledge.document_processing.supported_formats');

        if ($file->getSize() > $maxSize) {
            throw new Exception('File size exceeds maximum allowed size');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $supportedFormats)) {
            throw new Exception('File format not supported');
        }
    }

    /**
     * Generate unique filename.
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    /**
     * Parse document content based on file type.
     */
    protected function parseDocument(KnowledgeDocument $document): string
    {
        $parserClass = $document->getParserClass();
        
        if (!$parserClass || !class_exists($parserClass)) {
            throw new Exception('No parser available for file type: ' . $document->file_type);
        }

        $parser = new $parserClass();
        $filePath = Storage::path($document->file_path);

        return $parser->parse($filePath);
    }

    /**
     * Create chunks from document content.
     */
    protected function createChunks(KnowledgeDocument $document, string $content): array
    {
        $chunkSize = $document->knowledgeBase->chunk_size;
        $chunkOverlap = $document->knowledgeBase->chunk_overlap;

        $chunks = [];
        $contentLength = strlen($content);
        $position = 0;
        $chunkIndex = 0;

        while ($position < $contentLength) {
            $endPosition = min($position + $chunkSize, $contentLength);
            $chunkContent = substr($content, $position, $endPosition - $position);

            // Create chunk record
            $chunk = KnowledgeChunk::create([
                'knowledge_document_id' => $document->id,
                'knowledge_base_id' => $document->knowledge_base_id,
                'content' => $chunkContent,
                'chunk_index' => $chunkIndex,
                'start_position' => $position,
                'end_position' => $endPosition,
                'token_count' => $this->countTokens($chunkContent),
            ]);

            $chunks[] = $chunk;

            $position = $endPosition - $chunkOverlap;
            $chunkIndex++;
        }

        return $chunks;
    }

    /**
     * Generate embeddings for chunks.
     */
    protected function generateEmbeddings(KnowledgeDocument $document, array $chunks): void
    {
        $batchSize = config('knowledge.embedding.batch_size', 100);
        $embeddingModel = $document->knowledgeBase->embedding_model;

        $batches = array_chunk($chunks, $batchSize);

        foreach ($batches as $batch) {
            $texts = array_map(fn($chunk) => $chunk->content, $batch);
            
            $embeddings = $this->embeddingService->generateBatchEmbeddings(
                $texts,
                $embeddingModel
            );

            foreach ($batch as $index => $chunk) {
                $embedding = $embeddings[$index];
                
                // Store in vector database
                $vectorId = $this->vectorDbService->storeVector(
                    $document->knowledgeBase,
                    $embedding,
                    [
                        'chunk_id' => $chunk->id,
                        'document_id' => $document->id,
                        'content' => $chunk->content,
                    ]
                );

                // Update chunk with vector ID
                $chunk->update(['vector_id' => $vectorId]);
            }
        }
    }

    /**
     * Count tokens in text (simplified implementation).
     */
    protected function countTokens(string $text): int
    {
        // Simplified token counting - in production, use proper tokenizer
        return (int) (strlen($text) / 4);
    }

    /**
     * Queue document processing.
     */
    protected function queueDocumentProcessing(KnowledgeDocument $document): void
    {
        // In production, this would dispatch a job
        // For now, we'll process synchronously
        $this->processDocument($document);
    }

    /**
     * Rerank search results.
     */
    protected function rerankResults(string $query, array $results): array
    {
        // Simplified reranking - in production, use proper reranker
        return $results;
    }

    /**
     * Format search results.
     */
    protected function formatSearchResults(array $results): array
    {
        return array_map(function ($result) {
            return [
                'content' => $result['metadata']['content'] ?? '',
                'score' => $result['score'] ?? 0,
                'chunk_id' => $result['metadata']['chunk_id'] ?? null,
                'document_id' => $result['metadata']['document_id'] ?? null,
            ];
        }, $results);
    }
}
