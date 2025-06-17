<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AiModelController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\WorkflowController;
use App\Http\Controllers\Api\MemoryController;
use App\Http\Controllers\Api\McpToolController;
use App\Http\Controllers\Api\GenerationController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

// Authentication required routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // User routes
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'profile']);
        Route::put('/', [UserController::class, 'updateProfile']);
        Route::get('/usage-stats', [UserController::class, 'usageStats']);
        Route::get('/preferences', [UserController::class, 'preferences']);
        Route::put('/preferences', [UserController::class, 'updatePreferences']);
    });

    // AI Models routes
    Route::prefix('ai-models')->group(function () {
        Route::get('/', [AiModelController::class, 'index']);
        Route::get('/default', [AiModelController::class, 'default']);
        Route::get('/provider/{provider}', [AiModelController::class, 'byProvider']);
        Route::get('/{modelId}', [AiModelController::class, 'show']);
        Route::post('/call', [AiModelController::class, 'call']);
        Route::post('/stream', [AiModelController::class, 'stream']);
        Route::get('/usage/stats', [AiModelController::class, 'usage']);
    });

    // Conversations routes
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index']);
        Route::post('/', [ConversationController::class, 'store']);
        Route::get('/search', [ConversationController::class, 'search']);
        Route::get('/{conversationId}', [ConversationController::class, 'show']);
        Route::put('/{conversationId}', [ConversationController::class, 'update']);
        Route::delete('/{conversationId}', [ConversationController::class, 'destroy']);
        Route::post('/{conversationId}/messages', [ConversationController::class, 'sendMessage']);
        Route::get('/{conversationId}/messages', [ConversationController::class, 'messages']);
        Route::post('/{conversationId}/archive', [ConversationController::class, 'archive']);
        Route::post('/{conversationId}/unarchive', [ConversationController::class, 'unarchive']);
    });

    // Knowledge Bases routes
    Route::prefix('knowledge-bases')->group(function () {
        Route::get('/', [KnowledgeBaseController::class, 'index']);
        Route::post('/', [KnowledgeBaseController::class, 'store']);
        Route::get('/{knowledgeBaseId}', [KnowledgeBaseController::class, 'show']);
        Route::put('/{knowledgeBaseId}', [KnowledgeBaseController::class, 'update']);
        Route::delete('/{knowledgeBaseId}', [KnowledgeBaseController::class, 'destroy']);
        Route::post('/{knowledgeBaseId}/documents', [KnowledgeBaseController::class, 'uploadDocument']);
        Route::get('/{knowledgeBaseId}/documents', [KnowledgeBaseController::class, 'documents']);
        Route::delete('/{knowledgeBaseId}/documents/{documentId}', [KnowledgeBaseController::class, 'deleteDocument']);
        Route::post('/{knowledgeBaseId}/search', [KnowledgeBaseController::class, 'search']);
        Route::get('/{knowledgeBaseId}/statistics', [KnowledgeBaseController::class, 'statistics']);
    });

    // Workflows routes
    Route::prefix('workflows')->group(function () {
        Route::get('/', [WorkflowController::class, 'index']);
        Route::post('/', [WorkflowController::class, 'store']);
        Route::get('/templates', [WorkflowController::class, 'templates']);
        Route::get('/{workflowId}', [WorkflowController::class, 'show']);
        Route::put('/{workflowId}', [WorkflowController::class, 'update']);
        Route::delete('/{workflowId}', [WorkflowController::class, 'destroy']);
        Route::post('/{workflowId}/execute', [WorkflowController::class, 'execute']);
        Route::post('/{workflowId}/duplicate', [WorkflowController::class, 'duplicate']);
        Route::post('/{workflowId}/publish', [WorkflowController::class, 'publish']);
        Route::get('/{workflowId}/executions', [WorkflowController::class, 'executions']);
        Route::post('/templates/{templateId}/clone', [WorkflowController::class, 'cloneFromTemplate']);
    });

    // Workflow Executions routes
    Route::prefix('workflow-executions')->group(function () {
        Route::get('/{executionId}', [WorkflowController::class, 'executionStatus']);
        Route::post('/{executionId}/cancel', [WorkflowController::class, 'cancelExecution']);
        Route::get('/{executionId}/logs', [WorkflowController::class, 'executionLogs']);
    });

    // Memory routes
    Route::prefix('memory')->group(function () {
        Route::get('/', [MemoryController::class, 'index']);
        Route::post('/', [MemoryController::class, 'store']);
        Route::get('/search', [MemoryController::class, 'search']);
        Route::get('/statistics', [MemoryController::class, 'statistics']);
        Route::get('/type/{type}', [MemoryController::class, 'byType']);
        Route::get('/context/{context}', [MemoryController::class, 'byContext']);
        Route::get('/{memoryId}', [MemoryController::class, 'show']);
        Route::put('/{memoryId}', [MemoryController::class, 'update']);
        Route::delete('/{memoryId}', [MemoryController::class, 'destroy']);
        Route::post('/cleanup', [MemoryController::class, 'cleanup']);
        Route::post('/consolidate', [MemoryController::class, 'consolidate']);
    });

    // MCP Tools routes
    Route::prefix('mcp-tools')->group(function () {
        Route::get('/', [McpToolController::class, 'index']);
        Route::get('/categories', [McpToolController::class, 'categories']);
        Route::get('/{toolId}', [McpToolController::class, 'show']);
        Route::post('/{toolId}/execute', [McpToolController::class, 'execute']);
        Route::get('/{toolId}/executions', [McpToolController::class, 'executions']);
        Route::post('/discover', [McpToolController::class, 'discover']);
    });

    // Generation routes
    Route::prefix('generation')->group(function () {
        Route::get('/tasks', [GenerationController::class, 'tasks']);
        Route::post('/text', [GenerationController::class, 'generateText']);
        Route::post('/image', [GenerationController::class, 'generateImage']);
        Route::post('/video', [GenerationController::class, 'generateVideo']);
        Route::post('/audio', [GenerationController::class, 'generateAudio']);
        Route::get('/tasks/{taskId}', [GenerationController::class, 'taskStatus']);
        Route::post('/tasks/{taskId}/cancel', [GenerationController::class, 'cancelTask']);
    });

    // Analytics and Monitoring routes
    Route::prefix('analytics')->group(function () {
        Route::get('/dashboard', [UserController::class, 'dashboard']);
        Route::get('/usage', [UserController::class, 'usageAnalytics']);
        Route::get('/costs', [UserController::class, 'costAnalytics']);
        Route::get('/performance', [UserController::class, 'performanceAnalytics']);
    });

    // System routes (admin only)
    Route::middleware(['role:admin'])->prefix('system')->group(function () {
        Route::get('/status', function () {
            return response()->json([
                'database' => 'connected',
                'redis' => 'connected',
                'queue' => 'running',
                'storage' => 'available',
                'ai_providers' => [
                    'openai' => 'connected',
                    'anthropic' => 'connected',
                    'google' => 'connected',
                ],
                'vector_databases' => [
                    'pinecone' => 'connected',
                    'weaviate' => 'available',
                    'qdrant' => 'available',
                ],
            ]);
        });
        
        Route::get('/metrics', function () {
            return response()->json([
                'total_users' => \App\Models\User::count(),
                'total_conversations' => \App\Models\Conversation::count(),
                'total_knowledge_bases' => \App\Models\KnowledgeBase::count(),
                'total_workflows' => \App\Models\Workflow::count(),
                'total_ai_requests' => \App\Models\AiModelUsage::count(),
                'total_cost' => \App\Models\AiModelUsage::sum('cost'),
            ]);
        });
    });
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
    ], 404);
});
