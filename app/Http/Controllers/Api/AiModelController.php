<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiModelService;
use App\Models\AiModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AiModelController extends Controller
{
    protected AiModelService $aiModelService;

    public function __construct(AiModelService $aiModelService)
    {
        $this->aiModelService = $aiModelService;
    }

    /**
     * Get available AI models for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $models = $this->aiModelService->getAvailableModels($user);

            return $this->success($models->map(function ($model) {
                return [
                    'id' => $model->id,
                    'name' => $model->name,
                    'display_name' => $model->display_name,
                    'provider' => $model->provider,
                    'model_id' => $model->model_id,
                    'description' => $model->description,
                    'capabilities' => $model->capabilities,
                    'max_tokens' => $model->max_tokens,
                    'supports_streaming' => $model->supports_streaming,
                    'supports_functions' => $model->supports_functions,
                    'supports_vision' => $model->supports_vision,
                    'pricing' => $model->pricing,
                    'is_default' => $model->is_default,
                ];
            }));

        } catch (\Exception $e) {
            return $this->error('Failed to fetch AI models: ' . $e->getMessage());
        }
    }

    /**
     * Get models by provider.
     */
    public function byProvider(Request $request, string $provider): JsonResponse
    {
        try {
            $models = $this->aiModelService->getModelsByProvider($provider);

            return $this->success($models->map(function ($model) {
                return [
                    'id' => $model->id,
                    'name' => $model->name,
                    'display_name' => $model->display_name,
                    'model_id' => $model->model_id,
                    'description' => $model->description,
                    'capabilities' => $model->capabilities,
                    'max_tokens' => $model->max_tokens,
                    'pricing' => $model->pricing,
                ];
            }));

        } catch (\Exception $e) {
            return $this->error('Failed to fetch models for provider: ' . $e->getMessage());
        }
    }

    /**
     * Call an AI model.
     */
    public function call(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_id' => 'required|exists:ai_models,id',
            'prompt' => 'required|string|max:50000',
            'parameters' => 'sometimes|array',
            'parameters.temperature' => 'sometimes|numeric|between:0,2',
            'parameters.max_tokens' => 'sometimes|integer|min:1|max:32000',
            'parameters.stream' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $request->user();
            $model = AiModel::findOrFail($request->model_id);
            $prompt = $request->prompt;
            $parameters = $request->parameters ?? [];

            $response = $this->aiModelService->callModel(
                $model,
                $user,
                $prompt,
                $parameters
            );

            if (!$response['success']) {
                return $this->error($response['error']);
            }

            return $this->success([
                'content' => $response['content'],
                'usage' => $response['usage'],
                'cost' => $response['cost'],
                'response_time_ms' => $response['response_time_ms'],
                'request_id' => $response['request_id'],
            ]);

        } catch (\Exception $e) {
            return $this->error('AI model call failed: ' . $e->getMessage());
        }
    }

    /**
     * Stream an AI model response.
     */
    public function stream(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $validator = Validator::make($request->all(), [
            'model_id' => 'required|exists:ai_models,id',
            'prompt' => 'required|string|max:50000',
            'parameters' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->stream(function () use ($validator) {
                echo "data: " . json_encode(['error' => $validator->errors()]) . "\n\n";
            }, 200, [
                'Content-Type' => 'text/plain',
                'Cache-Control' => 'no-cache',
            ]);
        }

        $user = $request->user();
        $model = AiModel::findOrFail($request->model_id);
        $prompt = $request->prompt;
        $parameters = array_merge($request->parameters ?? [], ['stream' => true]);

        return response()->stream(function () use ($model, $user, $prompt, $parameters) {
            try {
                foreach ($this->aiModelService->streamModel($model, $user, $prompt, $parameters) as $chunk) {
                    echo "data: " . json_encode($chunk) . "\n\n";
                    ob_flush();
                    flush();
                }
            } catch (\Exception $e) {
                echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Get usage statistics for the authenticated user.
     */
    public function usage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'sometimes|in:day,week,month,year',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $request->user();
            $period = $request->period ?? 'month';

            $stats = $this->aiModelService->getUserUsageStats($user, $period);

            return $this->success($stats);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch usage statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get detailed model information.
     */
    public function show(Request $request, int $modelId): JsonResponse
    {
        try {
            $user = $request->user();
            $model = AiModel::findOrFail($modelId);

            // Check if user can access this model
            $availableModels = $this->aiModelService->getAvailableModels($user);
            if (!$availableModels->contains('id', $modelId)) {
                return $this->forbidden('You do not have access to this model');
            }

            return $this->success([
                'id' => $model->id,
                'name' => $model->name,
                'display_name' => $model->display_name,
                'provider' => $model->provider,
                'model_id' => $model->model_id,
                'description' => $model->description,
                'capabilities' => $model->capabilities,
                'parameters' => $model->parameters,
                'pricing' => $model->pricing,
                'max_tokens' => $model->max_tokens,
                'supports_streaming' => $model->supports_streaming,
                'supports_functions' => $model->supports_functions,
                'supports_vision' => $model->supports_vision,
                'rate_limits' => $model->rate_limits,
                'is_default' => $model->is_default,
                'created_at' => $model->created_at,
                'updated_at' => $model->updated_at,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch model details: ' . $e->getMessage());
        }
    }

    /**
     * Get default model.
     */
    public function default(Request $request): JsonResponse
    {
        try {
            $provider = $request->query('provider');
            $model = $this->aiModelService->getDefaultModel($provider);

            if (!$model) {
                return $this->notFound('No default model found');
            }

            return $this->success([
                'id' => $model->id,
                'name' => $model->name,
                'display_name' => $model->display_name,
                'provider' => $model->provider,
                'model_id' => $model->model_id,
                'max_tokens' => $model->max_tokens,
                'supports_streaming' => $model->supports_streaming,
                'supports_functions' => $model->supports_functions,
                'supports_vision' => $model->supports_vision,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch default model: ' . $e->getMessage());
        }
    }
}
