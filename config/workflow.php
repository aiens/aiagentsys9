<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Engine Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the workflow execution engine.
    |
    */

    'engine' => [
        'max_execution_time' => env('WORKFLOW_MAX_EXECUTION_TIME', 3600), // 1 hour
        'max_parallel_tasks' => env('WORKFLOW_MAX_PARALLEL_TASKS', 10),
        'max_retry_attempts' => env('WORKFLOW_MAX_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WORKFLOW_RETRY_DELAY', 5), // seconds
        'memory_limit' => env('WORKFLOW_MEMORY_LIMIT', '512M'),
        'timeout_buffer' => env('WORKFLOW_TIMEOUT_BUFFER', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Node Types Configuration
    |--------------------------------------------------------------------------
    |
    | Available node types for workflow construction.
    |
    */

    'node_types' => [
        'ai_call' => [
            'class' => \App\Services\Workflow\Nodes\AiCallNode::class,
            'name' => 'AI Model Call',
            'description' => 'Call an AI model with a prompt',
            'category' => 'ai',
            'inputs' => ['prompt', 'model', 'parameters'],
            'outputs' => ['response', 'tokens_used', 'cost'],
            'configurable' => ['model', 'temperature', 'max_tokens', 'stream'],
        ],

        'condition' => [
            'class' => \App\Services\Workflow\Nodes\ConditionNode::class,
            'name' => 'Condition',
            'description' => 'Conditional branching based on input',
            'category' => 'logic',
            'inputs' => ['condition', 'true_path', 'false_path'],
            'outputs' => ['result'],
            'configurable' => ['condition_type', 'operator', 'value'],
        ],

        'data_transform' => [
            'class' => \App\Services\Workflow\Nodes\DataTransformNode::class,
            'name' => 'Data Transform',
            'description' => 'Transform data using various operations',
            'category' => 'data',
            'inputs' => ['input_data'],
            'outputs' => ['output_data'],
            'configurable' => ['transform_type', 'parameters'],
        ],

        'api_call' => [
            'class' => \App\Services\Workflow\Nodes\ApiCallNode::class,
            'name' => 'API Call',
            'description' => 'Make HTTP API calls',
            'category' => 'integration',
            'inputs' => ['url', 'method', 'headers', 'body'],
            'outputs' => ['response', 'status_code', 'headers'],
            'configurable' => ['method', 'timeout', 'retry_count'],
        ],

        'knowledge_search' => [
            'class' => \App\Services\Workflow\Nodes\KnowledgeSearchNode::class,
            'name' => 'Knowledge Search',
            'description' => 'Search knowledge base',
            'category' => 'knowledge',
            'inputs' => ['query', 'knowledge_base_id'],
            'outputs' => ['results', 'sources'],
            'configurable' => ['top_k', 'similarity_threshold', 'search_strategy'],
        ],

        'memory_store' => [
            'class' => \App\Services\Workflow\Nodes\MemoryStoreNode::class,
            'name' => 'Memory Store',
            'description' => 'Store data in memory system',
            'category' => 'memory',
            'inputs' => ['key', 'value', 'memory_type'],
            'outputs' => ['success'],
            'configurable' => ['memory_type', 'ttl', 'importance'],
        ],

        'memory_retrieve' => [
            'class' => \App\Services\Workflow\Nodes\MemoryRetrieveNode::class,
            'name' => 'Memory Retrieve',
            'description' => 'Retrieve data from memory system',
            'category' => 'memory',
            'inputs' => ['key', 'memory_type'],
            'outputs' => ['value', 'found'],
            'configurable' => ['memory_type', 'fallback_value'],
        ],

        'file_read' => [
            'class' => \App\Services\Workflow\Nodes\FileReadNode::class,
            'name' => 'File Read',
            'description' => 'Read file content',
            'category' => 'file',
            'inputs' => ['file_path'],
            'outputs' => ['content', 'metadata'],
            'configurable' => ['encoding', 'max_size'],
        ],

        'file_write' => [
            'class' => \App\Services\Workflow\Nodes\FileWriteNode::class,
            'name' => 'File Write',
            'description' => 'Write content to file',
            'category' => 'file',
            'inputs' => ['file_path', 'content'],
            'outputs' => ['success', 'file_size'],
            'configurable' => ['encoding', 'append_mode'],
        ],

        'loop' => [
            'class' => \App\Services\Workflow\Nodes\LoopNode::class,
            'name' => 'Loop',
            'description' => 'Iterate over data or repeat operations',
            'category' => 'logic',
            'inputs' => ['items', 'loop_body'],
            'outputs' => ['results'],
            'configurable' => ['loop_type', 'max_iterations', 'break_condition'],
        ],

        'parallel' => [
            'class' => \App\Services\Workflow\Nodes\ParallelNode::class,
            'name' => 'Parallel Execution',
            'description' => 'Execute multiple branches in parallel',
            'category' => 'logic',
            'inputs' => ['branches'],
            'outputs' => ['results'],
            'configurable' => ['max_parallel', 'wait_for_all'],
        ],

        'mcp_tool' => [
            'class' => \App\Services\Workflow\Nodes\McpToolNode::class,
            'name' => 'MCP Tool',
            'description' => 'Execute MCP tool',
            'category' => 'tools',
            'inputs' => ['tool_name', 'parameters'],
            'outputs' => ['result', 'metadata'],
            'configurable' => ['tool_name', 'timeout', 'sandbox_enabled'],
        ],

        'generate_media' => [
            'class' => \App\Services\Workflow\Nodes\GenerateMediaNode::class,
            'name' => 'Generate Media',
            'description' => 'Generate images, videos, or audio',
            'category' => 'generation',
            'inputs' => ['prompt', 'media_type', 'parameters'],
            'outputs' => ['media_url', 'metadata'],
            'configurable' => ['media_type', 'quality', 'dimensions'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution Strategies
    |--------------------------------------------------------------------------
    |
    | Different strategies for workflow execution.
    |
    */

    'execution_strategies' => [
        'sequential' => \App\Services\Workflow\Strategies\SequentialStrategy::class,
        'parallel' => \App\Services\Workflow\Strategies\ParallelStrategy::class,
        'optimized' => \App\Services\Workflow\Strategies\OptimizedStrategy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for workflow error handling.
    |
    */

    'error_handling' => [
        'default_strategy' => env('WORKFLOW_ERROR_STRATEGY', 'stop'), // stop, continue, retry
        'log_errors' => env('WORKFLOW_LOG_ERRORS', true),
        'notify_on_error' => env('WORKFLOW_NOTIFY_ON_ERROR', false),
        'error_notification_channels' => ['mail', 'slack'],
        'max_error_count' => env('WORKFLOW_MAX_ERROR_COUNT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for workflow monitoring and logging.
    |
    */

    'monitoring' => [
        'enabled' => env('WORKFLOW_MONITORING_ENABLED', true),
        'log_level' => env('WORKFLOW_LOG_LEVEL', 'info'),
        'track_performance' => env('WORKFLOW_TRACK_PERFORMANCE', true),
        'store_execution_history' => env('WORKFLOW_STORE_HISTORY', true),
        'history_retention_days' => env('WORKFLOW_HISTORY_RETENTION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    |
    | Configuration for workflow scheduling.
    |
    */

    'scheduling' => [
        'enabled' => env('WORKFLOW_SCHEDULING_ENABLED', true),
        'default_queue' => env('WORKFLOW_DEFAULT_QUEUE', 'workflows'),
        'high_priority_queue' => env('WORKFLOW_HIGH_PRIORITY_QUEUE', 'workflows-high'),
        'low_priority_queue' => env('WORKFLOW_LOW_PRIORITY_QUEUE', 'workflows-low'),
        'max_concurrent_workflows' => env('WORKFLOW_MAX_CONCURRENT', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security configuration for workflow execution.
    |
    */

    'security' => [
        'sandbox_enabled' => env('WORKFLOW_SANDBOX_ENABLED', true),
        'allowed_domains' => env('WORKFLOW_ALLOWED_DOMAINS', ''),
        'blocked_domains' => env('WORKFLOW_BLOCKED_DOMAINS', ''),
        'max_file_size' => env('WORKFLOW_MAX_FILE_SIZE', 10 * 1024 * 1024), // 10MB
        'allowed_file_types' => ['txt', 'json', 'csv', 'xml', 'yaml'],
        'require_approval' => env('WORKFLOW_REQUIRE_APPROVAL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    |
    | Pre-built workflow templates.
    |
    */

    'templates' => [
        'document_analysis' => [
            'name' => 'Document Analysis',
            'description' => 'Analyze documents using AI',
            'category' => 'analysis',
            'template_path' => 'workflows/templates/document_analysis.json',
        ],
        'content_generation' => [
            'name' => 'Content Generation',
            'description' => 'Generate content using multiple AI models',
            'category' => 'generation',
            'template_path' => 'workflows/templates/content_generation.json',
        ],
        'data_processing' => [
            'name' => 'Data Processing Pipeline',
            'description' => 'Process and transform data',
            'category' => 'data',
            'template_path' => 'workflows/templates/data_processing.json',
        ],
    ],
];
