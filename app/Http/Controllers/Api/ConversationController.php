<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConversationService;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{
    protected ConversationService $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    /**
     * Get user's conversations.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->query('per_page', 20);
            $archived = $request->query('archived', false);

            $conversations = Conversation::where('user_id', $user->id)
                ->when($archived, fn($q) => $q->archived(), fn($q) => $q->active())
                ->with(['latestMessage'])
                ->byLastMessage()
                ->paginate($perPage);

            return $this->paginated($conversations->through(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'message_count' => $conversation->message_count,
                    'total_tokens' => $conversation->total_tokens,
                    'total_cost' => $conversation->total_cost,
                    'is_archived' => $conversation->is_archived,
                    'last_message_at' => $conversation->last_message_at,
                    'latest_message' => $conversation->latestMessage ? [
                        'role' => $conversation->latestMessage->role,
                        'content' => substr($conversation->latestMessage->content, 0, 100) . '...',
                        'created_at' => $conversation->latestMessage->created_at,
                    ] : null,
                    'created_at' => $conversation->created_at,
                ];
            }));

        } catch (\Exception $e) {
            return $this->error('Failed to fetch conversations: ' . $e->getMessage());
        }
    }

    /**
     * Create a new conversation.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'settings' => 'sometimes|array',
            'settings.model' => 'sometimes|string',
            'settings.temperature' => 'sometimes|numeric|between:0,2',
            'settings.max_tokens' => 'sometimes|integer|min:1|max:32000',
            'settings.system_prompt' => 'sometimes|string|max:10000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $request->user();
            $conversation = $this->conversationService->createConversation($user, $request->all());

            return $this->created([
                'id' => $conversation->id,
                'title' => $conversation->title,
                'settings' => $conversation->settings,
                'message_count' => $conversation->message_count,
                'created_at' => $conversation->created_at,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to create conversation: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific conversation.
     */
    public function show(Request $request, int $conversationId): JsonResponse
    {
        try {
            $user = $request->user();
            $conversation = Conversation::where('user_id', $user->id)
                ->findOrFail($conversationId);

            return $this->success([
                'id' => $conversation->id,
                'title' => $conversation->title,
                'settings' => $conversation->getSettingsWithDefaults(),
                'message_count' => $conversation->message_count,
                'total_tokens' => $conversation->total_tokens,
                'total_cost' => $conversation->total_cost,
                'is_archived' => $conversation->is_archived,
                'last_message_at' => $conversation->last_message_at,
                'created_at' => $conversation->created_at,
                'updated_at' => $conversation->updated_at,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch conversation: ' . $e->getMessage());
        }
    }

    /**
     * Update a conversation.
     */
    public function update(Request $request, int $conversationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'settings' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $request->user();
            $conversation = Conversation::where('user_id', $user->id)
                ->findOrFail($conversationId);

            $conversation->update($request->only(['title', 'settings']));

            return $this->success([
                'id' => $conversation->id,
                'title' => $conversation->title,
                'settings' => $conversation->settings,
                'updated_at' => $conversation->updated_at,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to update conversation: ' . $e->getMessage());
        }
    }

    /**
     * Delete a conversation.
     */
    public function destroy(Request $request, int $conversationId): JsonResponse
    {
        try {
            $user = $request->user();
            $conversation = Conversation::where('user_id', $user->id)
                ->findOrFail($conversationId);

            $conversation->delete();

            return $this->noContent();

        } catch (\Exception $e) {
            return $this->error('Failed to delete conversation: ' . $e->getMessage());
        }
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, int $conversationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:50000',
            'model' => 'sometimes|string',
            'temperature' => 'sometimes|numeric|between:0,2',
            'max_tokens' => 'sometimes|integer|min:1|max:32000',
            'stream' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $request->user();
            $conversation = Conversation::where('user_id', $user->id)
                ->findOrFail($conversationId);

            $options = $request->only(['model', 'temperature', 'max_tokens']);

            if ($request->boolean('stream')) {
                return $this->streamMessage($conversation, $request->content, $options);
            }

            $message = $this->conversationService->sendMessage(
                $conversation,
                $request->content,
                $options
            );

            return $this->success([
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'ai_model_id' => $message->ai_model_id,
                'input_tokens' => $message->input_tokens,
                'output_tokens' => $message->output_tokens,
                'cost' => $message->cost,
                'response_time_ms' => $message->response_time_ms,
                'metadata' => $message->metadata,
                'created_at' => $message->created_at,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to send message: ' . $e->getMessage());
        }
    }

    /**
     * Stream a message response.
     */
    protected function streamMessage(Conversation $conversation, string $content, array $options): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->stream(function () use ($conversation, $content, $options) {
            try {
                foreach ($this->conversationService->streamMessage($conversation, $content, $options) as $chunk) {
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
     * Get conversation messages.
     */
    public function messages(Request $request, int $conversationId): JsonResponse
    {
        try {
            $user = $request->user();
            $conversation = Conversation::where('user_id', $user->id)
                ->findOrFail($conversationId);

            $limit = $request->query('limit', 50);
            $messages = $this->conversationService->getHistory($conversation, $limit);

            return $this->success($messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'ai_model_id' => $message->ai_model_id,
                    'input_tokens' => $message->input_tokens,
                    'output_tokens' => $message->output_tokens,
                    'cost' => $message->cost,
                    'response_time_ms' => $message->response_time_ms,
                    'metadata' => $message->metadata,
                    'created_at' => $message->created_at,
                ];
            }));

        } catch (\Exception $e) {
            return $this->error('Failed to fetch messages: ' . $e->getMessage());
        }
    }

    /**
     * Archive a conversation.
     */
    public function archive(Request $request, int $conversationId): JsonResponse
    {
        try {
            $user = $request->user();
            $conversation = Conversation::where('user_id', $user->id)
                ->findOrFail($conversationId);

            $conversation->archive();

            return $this->success(['is_archived' => true]);

        } catch (\Exception $e) {
            return $this->error('Failed to archive conversation: ' . $e->getMessage());
        }
    }

    /**
     * Unarchive a conversation.
     */
    public function unarchive(Request $request, int $conversationId): JsonResponse
    {
        try {
            $user = $request->user();
            $conversation = Conversation::where('user_id', $user->id)
                ->findOrFail($conversationId);

            $conversation->unarchive();

            return $this->success(['is_archived' => false]);

        } catch (\Exception $e) {
            return $this->error('Failed to unarchive conversation: ' . $e->getMessage());
        }
    }

    /**
     * Search conversations.
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:255',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $request->user();
            $query = $request->query;
            $limit = $request->limit ?? 20;

            $conversations = $this->conversationService->searchConversations($user, $query, $limit);

            return $this->success($conversations->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'message_count' => $conversation->message_count,
                    'last_message_at' => $conversation->last_message_at,
                    'latest_message' => $conversation->latestMessage ? [
                        'content' => substr($conversation->latestMessage->content, 0, 100) . '...',
                        'created_at' => $conversation->latestMessage->created_at,
                    ] : null,
                    'created_at' => $conversation->created_at,
                ];
            }));

        } catch (\Exception $e) {
            return $this->error('Failed to search conversations: ' . $e->getMessage());
        }
    }
}
