<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP (Model Context Protocol) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for MCP tool integration and management.
    |
    */

    'enabled' => env('MCP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Tool Discovery and Registration
    |--------------------------------------------------------------------------
    |
    | Configuration for discovering and registering MCP tools.
    |
    */

    'discovery' => [
        'auto_discovery' => env('MCP_AUTO_DISCOVERY', true),
        'discovery_paths' => [
            storage_path('mcp/tools'),
            base_path('mcp/tools'),
        ],
        'discovery_interval' => env('MCP_DISCOVERY_INTERVAL', 3600), // seconds
        'allowed_protocols' => ['http', 'https', 'stdio'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool Execution
    |--------------------------------------------------------------------------
    |
    | Configuration for MCP tool execution.
    |
    */

    'execution' => [
        'sandbox_enabled' => env('MCP_SANDBOX_ENABLED', true),
        'max_execution_time' => env('MCP_MAX_EXECUTION_TIME', 300), // 5 minutes
        'max_memory_usage' => env('MCP_MAX_MEMORY_USAGE', '256M'),
        'max_concurrent_tools' => env('MCP_MAX_CONCURRENT_TOOLS', 10),
        'timeout_buffer' => env('MCP_TIMEOUT_BUFFER', 30), // seconds
        'retry_attempts' => env('MCP_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('MCP_RETRY_DELAY', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for MCP tool execution.
    |
    */

    'security' => [
        'require_authentication' => env('MCP_REQUIRE_AUTH', true),
        'allowed_origins' => env('MCP_ALLOWED_ORIGINS', '*'),
        'rate_limiting' => [
            'enabled' => env('MCP_RATE_LIMITING_ENABLED', true),
            'requests_per_minute' => env('MCP_REQUESTS_PER_MINUTE', 60),
            'requests_per_hour' => env('MCP_REQUESTS_PER_HOUR', 1000),
        ],
        'input_validation' => [
            'enabled' => env('MCP_INPUT_VALIDATION', true),
            'max_input_size' => env('MCP_MAX_INPUT_SIZE', 1024 * 1024), // 1MB
            'sanitize_inputs' => env('MCP_SANITIZE_INPUTS', true),
        ],
        'output_filtering' => [
            'enabled' => env('MCP_OUTPUT_FILTERING', true),
            'max_output_size' => env('MCP_MAX_OUTPUT_SIZE', 10 * 1024 * 1024), // 10MB
            'filter_sensitive_data' => env('MCP_FILTER_SENSITIVE_DATA', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Built-in Tools
    |--------------------------------------------------------------------------
    |
    | Configuration for built-in MCP tools.
    |
    */

    'builtin_tools' => [
        'file_operations' => [
            'enabled' => env('MCP_FILE_OPERATIONS_ENABLED', true),
            'class' => \App\Services\Mcp\Tools\FileOperationsTool::class,
            'allowed_paths' => [
                storage_path('app/mcp'),
                storage_path('app/uploads'),
            ],
            'allowed_extensions' => ['txt', 'json', 'csv', 'xml', 'yaml', 'md'],
            'max_file_size' => env('MCP_FILE_MAX_SIZE', 10 * 1024 * 1024), // 10MB
        ],

        'web_scraping' => [
            'enabled' => env('MCP_WEB_SCRAPING_ENABLED', true),
            'class' => \App\Services\Mcp\Tools\WebScrapingTool::class,
            'allowed_domains' => env('MCP_ALLOWED_DOMAINS', ''),
            'blocked_domains' => env('MCP_BLOCKED_DOMAINS', 'localhost,127.0.0.1'),
            'max_page_size' => env('MCP_MAX_PAGE_SIZE', 5 * 1024 * 1024), // 5MB
            'timeout' => env('MCP_WEB_TIMEOUT', 30),
            'user_agent' => env('MCP_USER_AGENT', 'Laravel-AI-Agent/1.0'),
        ],

        'database_query' => [
            'enabled' => env('MCP_DATABASE_QUERY_ENABLED', false),
            'class' => \App\Services\Mcp\Tools\DatabaseQueryTool::class,
            'allowed_connections' => ['mysql', 'pgsql'],
            'read_only' => env('MCP_DATABASE_READ_ONLY', true),
            'allowed_tables' => env('MCP_ALLOWED_TABLES', ''),
            'blocked_tables' => env('MCP_BLOCKED_TABLES', 'users,password_resets'),
        ],

        'api_client' => [
            'enabled' => env('MCP_API_CLIENT_ENABLED', true),
            'class' => \App\Services\Mcp\Tools\ApiClientTool::class,
            'allowed_hosts' => env('MCP_API_ALLOWED_HOSTS', ''),
            'blocked_hosts' => env('MCP_API_BLOCKED_HOSTS', 'localhost,127.0.0.1'),
            'max_response_size' => env('MCP_API_MAX_RESPONSE_SIZE', 10 * 1024 * 1024), // 10MB
            'timeout' => env('MCP_API_TIMEOUT', 30),
        ],

        'code_execution' => [
            'enabled' => env('MCP_CODE_EXECUTION_ENABLED', false),
            'class' => \App\Services\Mcp\Tools\CodeExecutionTool::class,
            'allowed_languages' => ['python', 'javascript', 'bash'],
            'sandbox_required' => env('MCP_CODE_SANDBOX_REQUIRED', true),
            'max_execution_time' => env('MCP_CODE_MAX_TIME', 60),
            'max_memory' => env('MCP_CODE_MAX_MEMORY', '128M'),
        ],

        'image_processing' => [
            'enabled' => env('MCP_IMAGE_PROCESSING_ENABLED', true),
            'class' => \App\Services\Mcp\Tools\ImageProcessingTool::class,
            'max_image_size' => env('MCP_IMAGE_MAX_SIZE', 20 * 1024 * 1024), // 20MB
            'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'max_dimensions' => env('MCP_IMAGE_MAX_DIMENSIONS', '4096x4096'),
        ],

        'text_processing' => [
            'enabled' => env('MCP_TEXT_PROCESSING_ENABLED', true),
            'class' => \App\Services\Mcp\Tools\TextProcessingTool::class,
            'max_text_length' => env('MCP_TEXT_MAX_LENGTH', 1000000), // 1M characters
            'supported_operations' => [
                'tokenize', 'summarize', 'translate', 'sentiment_analysis',
                'entity_extraction', 'keyword_extraction'
            ],
        ],

        'calendar' => [
            'enabled' => env('MCP_CALENDAR_ENABLED', true),
            'class' => \App\Services\Mcp\Tools\CalendarTool::class,
            'default_timezone' => env('MCP_CALENDAR_TIMEZONE', 'UTC'),
            'max_events_per_query' => env('MCP_CALENDAR_MAX_EVENTS', 100),
        ],

        'email' => [
            'enabled' => env('MCP_EMAIL_ENABLED', true),
            'class' => \App\Services\Mcp\Tools\EmailTool::class,
            'allowed_recipients' => env('MCP_EMAIL_ALLOWED_RECIPIENTS', ''),
            'max_attachments' => env('MCP_EMAIL_MAX_ATTACHMENTS', 5),
            'max_attachment_size' => env('MCP_EMAIL_MAX_ATTACHMENT_SIZE', 10 * 1024 * 1024), // 10MB
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External Tools
    |--------------------------------------------------------------------------
    |
    | Configuration for external MCP tools.
    |
    */

    'external_tools' => [
        'registry_url' => env('MCP_REGISTRY_URL', 'https://registry.mcp.tools'),
        'auto_update' => env('MCP_AUTO_UPDATE', false),
        'update_interval' => env('MCP_UPDATE_INTERVAL', 86400), // 24 hours
        'verify_signatures' => env('MCP_VERIFY_SIGNATURES', true),
        'allowed_publishers' => env('MCP_ALLOWED_PUBLISHERS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for MCP tool logging and monitoring.
    |
    */

    'logging' => [
        'enabled' => env('MCP_LOGGING_ENABLED', true),
        'log_level' => env('MCP_LOG_LEVEL', 'info'),
        'log_requests' => env('MCP_LOG_REQUESTS', true),
        'log_responses' => env('MCP_LOG_RESPONSES', true),
        'log_errors' => env('MCP_LOG_ERRORS', true),
        'retention_days' => env('MCP_LOG_RETENTION_DAYS', 30),
    ],

    'monitoring' => [
        'enabled' => env('MCP_MONITORING_ENABLED', true),
        'track_performance' => env('MCP_TRACK_PERFORMANCE', true),
        'track_usage' => env('MCP_TRACK_USAGE', true),
        'alert_on_errors' => env('MCP_ALERT_ON_ERRORS', true),
        'alert_threshold' => env('MCP_ALERT_THRESHOLD', 10), // errors per hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Configuration for MCP tool result caching.
    |
    */

    'caching' => [
        'enabled' => env('MCP_CACHING_ENABLED', true),
        'default_ttl' => env('MCP_CACHE_TTL', 3600), // 1 hour
        'cache_key_prefix' => env('MCP_CACHE_PREFIX', 'mcp_tool'),
        'cache_responses' => env('MCP_CACHE_RESPONSES', true),
        'cache_metadata' => env('MCP_CACHE_METADATA', true),
    ],
];
