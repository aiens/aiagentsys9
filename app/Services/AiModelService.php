<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\AiModelUsage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class AiModelService
{
    /**
     * Get all available AI models for a user.
     */
    public function getAvailableModels(User $user): Collection
    {
        return Cache::remember("ai_models_user_{$user->id}", 300, function () use ($user) {
            return AiModel::active()
                ->orderBy('priority', 'desc')
                ->orderBy('provider')
                ->orderBy('name')
                ->get()
                ->filter(function ($model) use ($user) {
                    return $this->canUserAccessModel($user, $model);
                });
        });
    }

    /**
     * Get models by provider.
     */
    public function getModelsByProvider(string $provider): Collection
    {
        return AiModel::active()
            ->byProvider($provider)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get the default model for a provider.
     */
    public function getDefaultModel(string $provider = null): ?AiModel
    {
        if ($provider) {
            return AiModel::getDefaultForProvider($provider);
        }

        $defaultProvider = config('ai_models.default');
        return AiModel::getDefaultForProvider($defaultProvider);
    }

    /**
     * Call an AI model with the given parameters.
     */
    public function callModel(
        AiModel $model,
        User $user,
        string $prompt,
        array $parameters = [],
        ?string $context = null
    ): array {
        $startTime = microtime(true);
        $requestId = $this->generateRequestId();

        try {
            // Validate rate limits
            $this->checkRateLimits($user, $model);

            // Prepare the request
            $requestData = $this->prepareRequest($model, $prompt, $parameters);

            // Make the API call
            $response = $this->makeApiCall($model, $requestData);

            // Process the response
            $processedResponse = $this->processResponse($model, $response);

            // Calculate metrics
            $endTime = microtime(true);
            $responseTime = (int)(($endTime - $startTime) * 1000);
            $cost = $model->calculateCost(
                $processedResponse['usage']['input_tokens'] ?? 0,
                $processedResponse['usage']['output_tokens'] ?? 0
            );

            // Log usage
            $this->logUsage($user, $model, $requestId, [
                'prompt' => $prompt,
                'response' => $processedResponse['content'],
                'parameters' => $parameters,
                'input_tokens' => $processedResponse['usage']['input_tokens'] ?? 0,
                'output_tokens' => $processedResponse['usage']['output_tokens'] ?? 0,
                'cost' => $cost,
                'response_time_ms' => $responseTime,
                'status' => 'success',
                'context' => $context,
            ]);

            return [
                'success' => true,
                'content' => $processedResponse['content'],
                'usage' => $processedResponse['usage'],
                'cost' => $cost,
                'response_time_ms' => $responseTime,
                'request_id' => $requestId,
            ];

        } catch (Exception $e) {
            $endTime = microtime(true);
            $responseTime = (int)(($endTime - $startTime) * 1000);

            // Log error
            $this->logUsage($user, $model, $requestId, [
                'prompt' => $prompt,
                'parameters' => $parameters,
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'context' => $context,
            ]);

            Log::error('AI Model API call failed', [
                'model' => $model->getFullIdentifier(),
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'request_id' => $requestId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'request_id' => $requestId,
            ];
        }
    }

    /**
     * Stream a response from an AI model.
     */
    public function streamModel(
        AiModel $model,
        User $user,
        string $prompt,
        array $parameters = [],
        ?string $context = null
    ): \Generator {
        if (!$model->supports_streaming) {
            throw new Exception('Model does not support streaming');
        }

        $requestId = $this->generateRequestId();
        $startTime = microtime(true);

        try {
            // Validate rate limits
            $this->checkRateLimits($user, $model);

            // Prepare the request
            $requestData = $this->prepareRequest($model, $prompt, array_merge($parameters, ['stream' => true]));

            // Make the streaming API call
            $stream = $this->makeStreamingApiCall($model, $requestData);

            $fullContent = '';
            $totalInputTokens = 0;
            $totalOutputTokens = 0;

            foreach ($stream as $chunk) {
                if (isset($chunk['content'])) {
                    $fullContent .= $chunk['content'];
                    yield $chunk;
                }

                if (isset($chunk['usage'])) {
                    $totalInputTokens = $chunk['usage']['input_tokens'] ?? 0;
                    $totalOutputTokens = $chunk['usage']['output_tokens'] ?? 0;
                }
            }

            // Log final usage
            $endTime = microtime(true);
            $responseTime = (int)(($endTime - $startTime) * 1000);
            $cost = $model->calculateCost($totalInputTokens, $totalOutputTokens);

            $this->logUsage($user, $model, $requestId, [
                'prompt' => $prompt,
                'response' => $fullContent,
                'parameters' => $parameters,
                'input_tokens' => $totalInputTokens,
                'output_tokens' => $totalOutputTokens,
                'cost' => $cost,
                'response_time_ms' => $responseTime,
                'status' => 'success',
                'context' => $context,
            ]);

        } catch (Exception $e) {
            Log::error('AI Model streaming failed', [
                'model' => $model->getFullIdentifier(),
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'request_id' => $requestId,
            ]);

            throw $e;
        }
    }

    /**
     * Get usage statistics for a user.
     */
    public function getUserUsageStats(User $user, ?string $period = 'month'): array
    {
        $query = AiModelUsage::where('user_id', $user->id);

        switch ($period) {
            case 'day':
                $query->where('created_at', '>=', now()->startOfDay());
                break;
            case 'week':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
            case 'year':
                $query->where('created_at', '>=', now()->startOfYear());
                break;
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_requests,
            SUM(input_tokens) as total_input_tokens,
            SUM(output_tokens) as total_output_tokens,
            SUM(cost) as total_cost,
            AVG(response_time_ms) as avg_response_time,
            COUNT(CASE WHEN status = "success" THEN 1 END) as successful_requests,
            COUNT(CASE WHEN status = "error" THEN 1 END) as failed_requests
        ')->first();

        return [
            'total_requests' => $stats->total_requests ?? 0,
            'total_tokens' => ($stats->total_input_tokens ?? 0) + ($stats->total_output_tokens ?? 0),
            'total_cost' => $stats->total_cost ?? 0,
            'avg_response_time_ms' => $stats->avg_response_time ?? 0,
            'success_rate' => $stats->total_requests > 0 
                ? (($stats->successful_requests ?? 0) / $stats->total_requests) * 100 
                : 0,
        ];
    }

    /**
     * Check if a user can access a specific model.
     */
    protected function canUserAccessModel(User $user, AiModel $model): bool
    {
        // Check if user has permission to use this model
        if ($user->hasPermissionTo("use_model_{$model->provider}")) {
            return true;
        }

        // Check if user has general AI model access
        if ($user->hasPermissionTo('use_ai_models')) {
            return true;
        }

        // Default models are accessible to all users
        return $model->is_default;
    }

    /**
     * Check rate limits for a user and model.
     */
    protected function checkRateLimits(User $user, AiModel $model): void
    {
        if (!config('ai_models.rate_limiting.enabled')) {
            return;
        }

        $key = "rate_limit_user_{$user->id}_model_{$model->id}";
        $requests = Cache::get($key, 0);
        $limit = config('ai_models.rate_limiting.requests_per_minute', 60);

        if ($requests >= $limit) {
            throw new Exception('Rate limit exceeded. Please try again later.');
        }

        Cache::put($key, $requests + 1, 60);
    }

    /**
     * Prepare the request data for the API call.
     */
    protected function prepareRequest(AiModel $model, string $prompt, array $parameters): array
    {
        $defaultParams = [
            'temperature' => 0.7,
            'max_tokens' => 2048,
        ];

        return array_merge($defaultParams, $parameters, [
            'model' => $model->model_id,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
        ]);
    }

    /**
     * Make the actual API call to the AI provider.
     */
    protected function makeApiCall(AiModel $model, array $requestData): array
    {
        // This would be implemented with actual API clients
        // For now, return a mock response
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Mock response from ' . $model->name
                    ]
                ]
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ]
        ];
    }

    /**
     * Make a streaming API call.
     */
    protected function makeStreamingApiCall(AiModel $model, array $requestData): \Generator
    {
        // Mock streaming response
        $content = 'Mock streaming response from ' . $model->name;
        $words = explode(' ', $content);

        foreach ($words as $word) {
            yield [
                'content' => $word . ' ',
                'usage' => [
                    'input_tokens' => 10,
                    'output_tokens' => count($words),
                ]
            ];
            usleep(100000); // 100ms delay
        }
    }

    /**
     * Process the API response.
     */
    protected function processResponse(AiModel $model, array $response): array
    {
        return [
            'content' => $response['choices'][0]['message']['content'] ?? '',
            'usage' => [
                'input_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
            ]
        ];
    }

    /**
     * Log the usage of an AI model.
     */
    protected function logUsage(User $user, AiModel $model, string $requestId, array $data): void
    {
        AiModelUsage::create([
            'user_id' => $user->id,
            'ai_model_id' => $model->id,
            'request_id' => $requestId,
            'prompt' => $data['prompt'] ?? null,
            'response' => $data['response'] ?? null,
            'parameters' => $data['parameters'] ?? null,
            'input_tokens' => $data['input_tokens'] ?? 0,
            'output_tokens' => $data['output_tokens'] ?? 0,
            'cost' => $data['cost'] ?? 0,
            'response_time_ms' => $data['response_time_ms'] ?? null,
            'status' => $data['status'],
            'error_message' => $data['error_message'] ?? null,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    /**
     * Generate a unique request ID.
     */
    protected function generateRequestId(): string
    {
        return 'req_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    }
}
