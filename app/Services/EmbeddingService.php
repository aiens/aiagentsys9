<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class EmbeddingService
{
    /**
     * Generate embedding for a single text.
     */
    public function generateEmbedding(string $text, string $model = null): array
    {
        $model = $model ?? config('knowledge.embedding.default_model');
        $modelConfig = config("knowledge.embedding.models.{$model}");

        if (!$modelConfig) {
            throw new Exception("Embedding model '{$model}' not configured");
        }

        // Check cache first
        $cacheKey = $this->getCacheKey($text, $model);
        if (config('knowledge.caching.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        try {
            $embedding = $this->callEmbeddingApi($text, $model, $modelConfig);

            // Cache the result
            if (config('knowledge.caching.enabled')) {
                $ttl = config('knowledge.caching.embeddings_ttl', 86400);
                Cache::put($cacheKey, $embedding, $ttl);
            }

            return $embedding;

        } catch (Exception $e) {
            Log::error('Embedding generation failed', [
                'model' => $model,
                'text_length' => strlen($text),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate embeddings for multiple texts in batch.
     */
    public function generateBatchEmbeddings(array $texts, string $model = null): array
    {
        $model = $model ?? config('knowledge.embedding.default_model');
        $modelConfig = config("knowledge.embedding.models.{$model}");

        if (!$modelConfig) {
            throw new Exception("Embedding model '{$model}' not configured");
        }

        $batchSize = config('knowledge.embedding.batch_size', 100);
        $embeddings = [];

        // Process in batches
        $batches = array_chunk($texts, $batchSize);

        foreach ($batches as $batch) {
            $batchEmbeddings = $this->processBatch($batch, $model, $modelConfig);
            $embeddings = array_merge($embeddings, $batchEmbeddings);
        }

        return $embeddings;
    }

    /**
     * Calculate similarity between two embeddings.
     */
    public function calculateSimilarity(array $embedding1, array $embedding2): float
    {
        return $this->cosineSimilarity($embedding1, $embedding2);
    }

    /**
     * Get embedding model information.
     */
    public function getModelInfo(string $model): array
    {
        return config("knowledge.embedding.models.{$model}", []);
    }

    /**
     * Process a batch of texts.
     */
    protected function processBatch(array $texts, string $model, array $modelConfig): array
    {
        $embeddings = [];
        $uncachedTexts = [];
        $uncachedIndices = [];

        // Check cache for each text
        if (config('knowledge.caching.enabled')) {
            foreach ($texts as $index => $text) {
                $cacheKey = $this->getCacheKey($text, $model);
                $cached = Cache::get($cacheKey);
                
                if ($cached) {
                    $embeddings[$index] = $cached;
                } else {
                    $uncachedTexts[] = $text;
                    $uncachedIndices[] = $index;
                }
            }
        } else {
            $uncachedTexts = $texts;
            $uncachedIndices = array_keys($texts);
        }

        // Generate embeddings for uncached texts
        if (!empty($uncachedTexts)) {
            $newEmbeddings = $this->callBatchEmbeddingApi($uncachedTexts, $model, $modelConfig);

            // Cache and store new embeddings
            foreach ($newEmbeddings as $i => $embedding) {
                $originalIndex = $uncachedIndices[$i];
                $embeddings[$originalIndex] = $embedding;

                // Cache the result
                if (config('knowledge.caching.enabled')) {
                    $cacheKey = $this->getCacheKey($uncachedTexts[$i], $model);
                    $ttl = config('knowledge.caching.embeddings_ttl', 86400);
                    Cache::put($cacheKey, $embedding, $ttl);
                }
            }
        }

        // Sort embeddings by original order
        ksort($embeddings);
        return array_values($embeddings);
    }

    /**
     * Call embedding API for a single text.
     */
    protected function callEmbeddingApi(string $text, string $model, array $modelConfig): array
    {
        $provider = $modelConfig['provider'];

        switch ($provider) {
            case 'openai':
                return $this->callOpenAiEmbedding($text, $model);
            
            default:
                throw new Exception("Embedding provider '{$provider}' not supported");
        }
    }

    /**
     * Call embedding API for multiple texts.
     */
    protected function callBatchEmbeddingApi(array $texts, string $model, array $modelConfig): array
    {
        $provider = $modelConfig['provider'];

        switch ($provider) {
            case 'openai':
                return $this->callOpenAiBatchEmbedding($texts, $model);
            
            default:
                throw new Exception("Embedding provider '{$provider}' not supported");
        }
    }

    /**
     * Call OpenAI embedding API.
     */
    protected function callOpenAiEmbedding(string $text, string $model): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('ai_models.providers.openai.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/embeddings', [
            'model' => $model,
            'input' => $text,
        ]);

        if (!$response->successful()) {
            throw new Exception('OpenAI API call failed: ' . $response->body());
        }

        $data = $response->json();
        return $data['data'][0]['embedding'] ?? [];
    }

    /**
     * Call OpenAI batch embedding API.
     */
    protected function callOpenAiBatchEmbedding(array $texts, string $model): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('ai_models.providers.openai.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/embeddings', [
            'model' => $model,
            'input' => $texts,
        ]);

        if (!$response->successful()) {
            throw new Exception('OpenAI API call failed: ' . $response->body());
        }

        $data = $response->json();
        return array_map(fn($item) => $item['embedding'], $data['data'] ?? []);
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new Exception('Vectors must have the same dimension');
        }

        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Generate cache key for text and model.
     */
    protected function getCacheKey(string $text, string $model): string
    {
        return 'embedding_' . $model . '_' . hash('sha256', $text);
    }
}
