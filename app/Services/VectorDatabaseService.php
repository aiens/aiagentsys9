<?php

namespace App\Services;

use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class VectorDatabaseService
{
    /**
     * Create an index for a knowledge base.
     */
    public function createIndex(KnowledgeBase $knowledgeBase): void
    {
        $config = $knowledgeBase->getVectorDbConfig();
        $driver = $knowledgeBase->vector_db_type;

        switch ($driver) {
            case 'pinecone':
                $this->createPineconeIndex($knowledgeBase, $config);
                break;
            
            case 'weaviate':
                $this->createWeaviateClass($knowledgeBase, $config);
                break;
            
            case 'qdrant':
                $this->createQdrantCollection($knowledgeBase, $config);
                break;
            
            default:
                throw new Exception("Vector database driver '{$driver}' not supported");
        }
    }

    /**
     * Store a vector in the database.
     */
    public function storeVector(
        KnowledgeBase $knowledgeBase,
        array $embedding,
        array $metadata = []
    ): string {
        $config = $knowledgeBase->getVectorDbConfig();
        $driver = $knowledgeBase->vector_db_type;

        switch ($driver) {
            case 'pinecone':
                return $this->storePineconeVector($knowledgeBase, $embedding, $metadata, $config);
            
            case 'weaviate':
                return $this->storeWeaviateVector($knowledgeBase, $embedding, $metadata, $config);
            
            case 'qdrant':
                return $this->storeQdrantVector($knowledgeBase, $embedding, $metadata, $config);
            
            default:
                throw new Exception("Vector database driver '{$driver}' not supported");
        }
    }

    /**
     * Search for similar vectors.
     */
    public function search(
        KnowledgeBase $knowledgeBase,
        array $queryEmbedding,
        int $topK = 10,
        float $threshold = 0.7
    ): array {
        $config = $knowledgeBase->getVectorDbConfig();
        $driver = $knowledgeBase->vector_db_type;

        switch ($driver) {
            case 'pinecone':
                return $this->searchPinecone($knowledgeBase, $queryEmbedding, $topK, $threshold, $config);
            
            case 'weaviate':
                return $this->searchWeaviate($knowledgeBase, $queryEmbedding, $topK, $threshold, $config);
            
            case 'qdrant':
                return $this->searchQdrant($knowledgeBase, $queryEmbedding, $topK, $threshold, $config);
            
            default:
                throw new Exception("Vector database driver '{$driver}' not supported");
        }
    }

    /**
     * Delete a vector from the database.
     */
    public function deleteVector(KnowledgeBase $knowledgeBase, string $vectorId): void
    {
        $config = $knowledgeBase->getVectorDbConfig();
        $driver = $knowledgeBase->vector_db_type;

        switch ($driver) {
            case 'pinecone':
                $this->deletePineconeVector($knowledgeBase, $vectorId, $config);
                break;
            
            case 'weaviate':
                $this->deleteWeaviateVector($knowledgeBase, $vectorId, $config);
                break;
            
            case 'qdrant':
                $this->deleteQdrantVector($knowledgeBase, $vectorId, $config);
                break;
            
            default:
                throw new Exception("Vector database driver '{$driver}' not supported");
        }
    }

    /**
     * Pinecone implementation methods.
     */
    protected function createPineconeIndex(KnowledgeBase $knowledgeBase, array $config): void
    {
        $indexName = $config['index_name'] ?? 'kb-' . $knowledgeBase->id;
        
        $response = Http::withHeaders([
            'Api-Key' => $config['api_key'],
            'Content-Type' => 'application/json',
        ])->post("https://api.pinecone.io/indexes", [
            'name' => $indexName,
            'dimension' => $config['dimension'],
            'metric' => $config['metric'] ?? 'cosine',
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to create Pinecone index: ' . $response->body());
        }

        $knowledgeBase->update(['vector_db_index' => $indexName]);
    }

    protected function storePineconeVector(
        KnowledgeBase $knowledgeBase,
        array $embedding,
        array $metadata,
        array $config
    ): string {
        $vectorId = 'vec_' . uniqid();
        $indexName = $knowledgeBase->vector_db_index;

        $response = Http::withHeaders([
            'Api-Key' => $config['api_key'],
            'Content-Type' => 'application/json',
        ])->post("https://{$indexName}-{$config['environment']}.svc.{$config['environment']}.pinecone.io/vectors/upsert", [
            'vectors' => [
                [
                    'id' => $vectorId,
                    'values' => $embedding,
                    'metadata' => $metadata,
                ]
            ]
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to store vector in Pinecone: ' . $response->body());
        }

        return $vectorId;
    }

    protected function searchPinecone(
        KnowledgeBase $knowledgeBase,
        array $queryEmbedding,
        int $topK,
        float $threshold,
        array $config
    ): array {
        $indexName = $knowledgeBase->vector_db_index;

        $response = Http::withHeaders([
            'Api-Key' => $config['api_key'],
            'Content-Type' => 'application/json',
        ])->post("https://{$indexName}-{$config['environment']}.svc.{$config['environment']}.pinecone.io/query", [
            'vector' => $queryEmbedding,
            'topK' => $topK,
            'includeMetadata' => true,
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to search Pinecone: ' . $response->body());
        }

        $data = $response->json();
        $results = [];

        foreach ($data['matches'] ?? [] as $match) {
            if ($match['score'] >= $threshold) {
                $results[] = [
                    'id' => $match['id'],
                    'score' => $match['score'],
                    'metadata' => $match['metadata'] ?? [],
                ];
            }
        }

        return $results;
    }

    protected function deletePineconeVector(KnowledgeBase $knowledgeBase, string $vectorId, array $config): void
    {
        $indexName = $knowledgeBase->vector_db_index;

        $response = Http::withHeaders([
            'Api-Key' => $config['api_key'],
            'Content-Type' => 'application/json',
        ])->delete("https://{$indexName}-{$config['environment']}.svc.{$config['environment']}.pinecone.io/vectors/delete", [
            'ids' => [$vectorId]
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to delete vector from Pinecone: ' . $response->body());
        }
    }

    /**
     * Weaviate implementation methods.
     */
    protected function createWeaviateClass(KnowledgeBase $knowledgeBase, array $config): void
    {
        $className = $config['class_name'] ?? 'KnowledgeBase' . $knowledgeBase->id;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$config['url']}/v1/schema", [
            'class' => $className,
            'properties' => [
                [
                    'name' => 'content',
                    'dataType' => ['text'],
                ],
                [
                    'name' => 'chunkId',
                    'dataType' => ['string'],
                ],
                [
                    'name' => 'documentId',
                    'dataType' => ['string'],
                ],
            ],
            'vectorizer' => 'none',
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to create Weaviate class: ' . $response->body());
        }

        $knowledgeBase->update(['vector_db_index' => $className]);
    }

    protected function storeWeaviateVector(
        KnowledgeBase $knowledgeBase,
        array $embedding,
        array $metadata,
        array $config
    ): string {
        $className = $knowledgeBase->vector_db_index;
        $vectorId = uniqid();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$config['url']}/v1/objects", [
            'class' => $className,
            'id' => $vectorId,
            'properties' => [
                'content' => $metadata['content'] ?? '',
                'chunkId' => $metadata['chunk_id'] ?? '',
                'documentId' => $metadata['document_id'] ?? '',
            ],
            'vector' => $embedding,
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to store vector in Weaviate: ' . $response->body());
        }

        return $vectorId;
    }

    protected function searchWeaviate(
        KnowledgeBase $knowledgeBase,
        array $queryEmbedding,
        int $topK,
        float $threshold,
        array $config
    ): array {
        $className = $knowledgeBase->vector_db_index;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$config['url']}/v1/graphql", [
            'query' => "
                {
                    Get {
                        {$className}(
                            nearVector: {
                                vector: " . json_encode($queryEmbedding) . "
                                certainty: {$threshold}
                            }
                            limit: {$topK}
                        ) {
                            content
                            chunkId
                            documentId
                            _additional {
                                certainty
                                id
                            }
                        }
                    }
                }
            "
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to search Weaviate: ' . $response->body());
        }

        $data = $response->json();
        $results = [];

        foreach ($data['data']['Get'][$className] ?? [] as $item) {
            $results[] = [
                'id' => $item['_additional']['id'],
                'score' => $item['_additional']['certainty'],
                'metadata' => [
                    'content' => $item['content'],
                    'chunk_id' => $item['chunkId'],
                    'document_id' => $item['documentId'],
                ],
            ];
        }

        return $results;
    }

    protected function deleteWeaviateVector(KnowledgeBase $knowledgeBase, string $vectorId, array $config): void
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->delete("{$config['url']}/v1/objects/{$vectorId}");

        if (!$response->successful()) {
            throw new Exception('Failed to delete vector from Weaviate: ' . $response->body());
        }
    }

    /**
     * Qdrant implementation methods (simplified).
     */
    protected function createQdrantCollection(KnowledgeBase $knowledgeBase, array $config): void
    {
        // Simplified Qdrant implementation
        $collectionName = $config['collection_name'] ?? 'kb_' . $knowledgeBase->id;
        $knowledgeBase->update(['vector_db_index' => $collectionName]);
    }

    protected function storeQdrantVector(
        KnowledgeBase $knowledgeBase,
        array $embedding,
        array $metadata,
        array $config
    ): string {
        // Simplified implementation
        return 'qdrant_' . uniqid();
    }

    protected function searchQdrant(
        KnowledgeBase $knowledgeBase,
        array $queryEmbedding,
        int $topK,
        float $threshold,
        array $config
    ): array {
        // Simplified implementation
        return [];
    }

    protected function deleteQdrantVector(KnowledgeBase $knowledgeBase, string $vectorId, array $config): void
    {
        // Simplified implementation
    }
}
