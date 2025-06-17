<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Models\AiModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

class ConversationService
{
    protected AiModelService $aiModelService;
    protected MemoryService $memoryService;

    public function __construct(
        AiModelService $aiModelService,
        MemoryService $memoryService
    ) {
        $this->aiModelService = $aiModelService;
        $this->memoryService = $memoryService;
    }

    /**
     * Create a new conversation.
     */
    public function createConversation(User $user, array $data = []): Conversation
    {
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => $data['title'] ?? null,
            'settings' => $data['settings'] ?? [],
        ]);

        Log::info('Conversation created', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        return $conversation;
    }

    /**
     * Send a message and get AI response.
     */
    public function sendMessage(
        Conversation $conversation,
        string $content,
        array $options = []
    ): ConversationMessage {
        $user = $conversation->user;
        $settings = array_merge($conversation->getSettingsWithDefaults(), $options);

        // Add user message
        $userMessage = $conversation->addMessage([
            'role' => 'user',
            'content' => $content,
        ]);

        try {
            // Get AI model
            $model = $this->getModelForConversation($conversation, $settings);

            // Prepare context
            $context = $this->prepareContext($conversation, $settings);

            // Retrieve relevant memories
            $memories = $this->memoryService->retrieveRelevantMemories(
                $user,
                $content,
                $conversation->id
            );

            // Add memories to context if any
            if (!empty($memories)) {
                $memoryContext = $this->formatMemoriesForContext($memories);
                $context = $this->addMemoryToContext($context, $memoryContext);
            }

            // Call AI model
            $response = $this->aiModelService->callModel(
                $model,
                $user,
                $this->formatContextForModel($context),
                $settings,
                "conversation_{$conversation->id}"
            );

            if (!$response['success']) {
                throw new Exception($response['error']);
            }

            // Add assistant message
            $assistantMessage = $conversation->addMessage([
                'role' => 'assistant',
                'content' => $response['content'],
                'ai_model_id' => $model->id,
                'input_tokens' => $response['usage']['input_tokens'] ?? 0,
                'output_tokens' => $response['usage']['output_tokens'] ?? 0,
                'cost' => $response['cost'] ?? 0,
                'response_time_ms' => $response['response_time_ms'] ?? null,
                'metadata' => [
                    'model_used' => $model->getFullIdentifier(),
                    'request_id' => $response['request_id'],
                ],
            ]);

            // Store important information in memory
            $this->storeConversationMemories($conversation, $userMessage, $assistantMessage);

            // Update conversation title if needed
            if (!$conversation->title) {
                $conversation->update(['title' => $conversation->generateTitle()]);
            }

            return $assistantMessage;

        } catch (Exception $e) {
            Log::error('Failed to send message', [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Stream a message response.
     */
    public function streamMessage(
        Conversation $conversation,
        string $content,
        array $options = []
    ): \Generator {
        $user = $conversation->user;
        $settings = array_merge($conversation->getSettingsWithDefaults(), $options);

        // Add user message
        $userMessage = $conversation->addMessage([
            'role' => 'user',
            'content' => $content,
        ]);

        try {
            // Get AI model
            $model = $this->getModelForConversation($conversation, $settings);

            if (!$model->supports_streaming) {
                throw new Exception('Model does not support streaming');
            }

            // Prepare context
            $context = $this->prepareContext($conversation, $settings);

            // Retrieve relevant memories
            $memories = $this->memoryService->retrieveRelevantMemories(
                $user,
                $content,
                $conversation->id
            );

            // Add memories to context if any
            if (!empty($memories)) {
                $memoryContext = $this->formatMemoriesForContext($memories);
                $context = $this->addMemoryToContext($context, $memoryContext);
            }

            // Stream AI model response
            $fullContent = '';
            $totalInputTokens = 0;
            $totalOutputTokens = 0;

            foreach ($this->aiModelService->streamModel(
                $model,
                $user,
                $this->formatContextForModel($context),
                $settings,
                "conversation_{$conversation->id}"
            ) as $chunk) {
                if (isset($chunk['content'])) {
                    $fullContent .= $chunk['content'];
                }

                if (isset($chunk['usage'])) {
                    $totalInputTokens = $chunk['usage']['input_tokens'] ?? 0;
                    $totalOutputTokens = $chunk['usage']['output_tokens'] ?? 0;
                }

                yield $chunk;
            }

            // Add assistant message after streaming is complete
            $assistantMessage = $conversation->addMessage([
                'role' => 'assistant',
                'content' => $fullContent,
                'ai_model_id' => $model->id,
                'input_tokens' => $totalInputTokens,
                'output_tokens' => $totalOutputTokens,
                'cost' => $model->calculateCost($totalInputTokens, $totalOutputTokens),
                'metadata' => [
                    'model_used' => $model->getFullIdentifier(),
                    'streaming' => true,
                ],
            ]);

            // Store important information in memory
            $this->storeConversationMemories($conversation, $userMessage, $assistantMessage);

            // Update conversation title if needed
            if (!$conversation->title) {
                $conversation->update(['title' => $conversation->generateTitle()]);
            }

        } catch (Exception $e) {
            Log::error('Failed to stream message', [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get conversation history.
     */
    public function getHistory(Conversation $conversation, int $limit = 50): Collection
    {
        return $conversation->messages()
            ->with('aiModel')
            ->latest()
            ->take($limit)
            ->get()
            ->reverse();
    }

    /**
     * Search conversations.
     */
    public function searchConversations(User $user, string $query, int $limit = 20): Collection
    {
        return Conversation::where('user_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhereHas('messages', function ($mq) use ($query) {
                      $mq->where('content', 'like', "%{$query}%");
                  });
            })
            ->with(['latestMessage', 'messages' => function ($q) {
                $q->take(3);
            }])
            ->byLastMessage()
            ->take($limit)
            ->get();
    }

    /**
     * Get model for conversation.
     */
    protected function getModelForConversation(Conversation $conversation, array $settings): AiModel
    {
        $modelName = $settings['model'] ?? null;
        
        if ($modelName) {
            $model = AiModel::where('model_id', $modelName)->active()->first();
            if ($model) {
                return $model;
            }
        }

        return $this->aiModelService->getDefaultModel();
    }

    /**
     * Prepare conversation context.
     */
    protected function prepareContext(Conversation $conversation, array $settings): array
    {
        $maxMessages = $settings['max_context_messages'] ?? 20;
        $context = $conversation->getContext($maxMessages);

        // Add system prompt if provided
        if (!empty($settings['system_prompt'])) {
            array_unshift($context, [
                'role' => 'system',
                'content' => $settings['system_prompt'],
            ]);
        }

        return $context;
    }

    /**
     * Format memories for context.
     */
    protected function formatMemoriesForContext(array $memories): string
    {
        if (empty($memories)) {
            return '';
        }

        $memoryText = "Relevant memories:\n";
        foreach ($memories as $memory) {
            $memoryText .= "- {$memory['value']}\n";
        }

        return $memoryText;
    }

    /**
     * Add memory to context.
     */
    protected function addMemoryToContext(array $context, string $memoryContext): array
    {
        // Add memory context as a system message
        array_unshift($context, [
            'role' => 'system',
            'content' => $memoryContext,
        ]);

        return $context;
    }

    /**
     * Format context for model.
     */
    protected function formatContextForModel(array $context): string
    {
        $formatted = '';
        foreach ($context as $message) {
            $role = ucfirst($message['role']);
            $formatted .= "{$role}: {$message['content']}\n\n";
        }

        return $formatted;
    }

    /**
     * Store conversation memories.
     */
    protected function storeConversationMemories(
        Conversation $conversation,
        ConversationMessage $userMessage,
        ConversationMessage $assistantMessage
    ): void {
        $user = $conversation->user;
        $context = "conversation_{$conversation->id}";

        // Store short-term memory of the exchange
        $this->memoryService->store(
            $user,
            'short_term',
            "exchange_{$userMessage->id}",
            "User: {$userMessage->content}\nAssistant: {$assistantMessage->content}",
            $context,
            ['importance' => 3]
        );

        // Extract and store important entities or facts
        $this->extractAndStoreImportantInfo($user, $userMessage, $assistantMessage, $context);
    }

    /**
     * Extract and store important information.
     */
    protected function extractAndStoreImportantInfo(
        User $user,
        ConversationMessage $userMessage,
        ConversationMessage $assistantMessage,
        string $context
    ): void {
        // This would use NLP to extract entities, facts, preferences, etc.
        // For now, we'll store basic information

        // Store user preferences mentioned
        if (preg_match('/I (like|prefer|love|hate|dislike) (.+)/i', $userMessage->content, $matches)) {
            $preference = $matches[1] . ' ' . $matches[2];
            $this->memoryService->store(
                $user,
                'long_term',
                'preference_' . md5($preference),
                "User preference: {$preference}",
                $context,
                ['importance' => 7]
            );
        }

        // Store factual information shared by user
        if (preg_match('/My name is (.+)/i', $userMessage->content, $matches)) {
            $this->memoryService->store(
                $user,
                'long_term',
                'user_name',
                "User's name: {$matches[1]}",
                $context,
                ['importance' => 10]
            );
        }
    }
}
