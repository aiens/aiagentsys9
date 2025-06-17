<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Model Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI model provider that will be used
    | by the application. You may set this to any of the providers defined
    | in the "providers" array below.
    |
    */

    'default' => env('AI_DEFAULT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | AI Model Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the AI model providers for your application.
    | Each provider can have multiple models with different capabilities.
    |
    */

    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'models' => [
                'gpt-4' => [
                    'name' => 'GPT-4',
                    'max_tokens' => 8192,
                    'supports_streaming' => true,
                    'supports_functions' => true,
                    'cost_per_1k_tokens' => ['input' => 0.03, 'output' => 0.06],
                ],
                'gpt-4-turbo' => [
                    'name' => 'GPT-4 Turbo',
                    'max_tokens' => 128000,
                    'supports_streaming' => true,
                    'supports_functions' => true,
                    'cost_per_1k_tokens' => ['input' => 0.01, 'output' => 0.03],
                ],
                'gpt-3.5-turbo' => [
                    'name' => 'GPT-3.5 Turbo',
                    'max_tokens' => 4096,
                    'supports_streaming' => true,
                    'supports_functions' => true,
                    'cost_per_1k_tokens' => ['input' => 0.0015, 'output' => 0.002],
                ],
            ],
        ],

        'anthropic' => [
            'driver' => 'anthropic',
            'api_key' => env('ANTHROPIC_API_KEY'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
            'models' => [
                'claude-3-opus-20240229' => [
                    'name' => 'Claude 3 Opus',
                    'max_tokens' => 200000,
                    'supports_streaming' => true,
                    'supports_functions' => true,
                    'cost_per_1k_tokens' => ['input' => 0.015, 'output' => 0.075],
                ],
                'claude-3-sonnet-20240229' => [
                    'name' => 'Claude 3 Sonnet',
                    'max_tokens' => 200000,
                    'supports_streaming' => true,
                    'supports_functions' => true,
                    'cost_per_1k_tokens' => ['input' => 0.003, 'output' => 0.015],
                ],
                'claude-3-haiku-20240307' => [
                    'name' => 'Claude 3 Haiku',
                    'max_tokens' => 200000,
                    'supports_streaming' => true,
                    'supports_functions' => true,
                    'cost_per_1k_tokens' => ['input' => 0.00025, 'output' => 0.00125],
                ],
            ],
        ],

        'google' => [
            'driver' => 'google',
            'api_key' => env('GOOGLE_AI_API_KEY'),
            'base_url' => env('GOOGLE_AI_BASE_URL', 'https://generativelanguage.googleapis.com'),
            'models' => [
                'gemini-pro' => [
                    'name' => 'Gemini Pro',
                    'max_tokens' => 32768,
                    'supports_streaming' => true,
                    'supports_functions' => true,
                    'cost_per_1k_tokens' => ['input' => 0.0005, 'output' => 0.0015],
                ],
                'gemini-pro-vision' => [
                    'name' => 'Gemini Pro Vision',
                    'max_tokens' => 16384,
                    'supports_streaming' => true,
                    'supports_functions' => false,
                    'supports_vision' => true,
                    'cost_per_1k_tokens' => ['input' => 0.0005, 'output' => 0.0015],
                ],
            ],
        ],

        'qianwen' => [
            'driver' => 'qianwen',
            'api_key' => env('QIANWEN_API_KEY'),
            'base_url' => env('QIANWEN_API_URL', 'https://dashscope.aliyuncs.com/api/v1'),
            'models' => [
                'qwen-turbo' => [
                    'name' => '通义千问 Turbo',
                    'max_tokens' => 8192,
                    'supports_streaming' => true,
                    'supports_functions' => true,
                ],
                'qwen-plus' => [
                    'name' => '通义千问 Plus',
                    'max_tokens' => 32768,
                    'supports_streaming' => true,
                    'supports_functions' => true,
                ],
            ],
        ],

        'wenxin' => [
            'driver' => 'wenxin',
            'api_key' => env('WENXIN_API_KEY'),
            'secret_key' => env('WENXIN_SECRET_KEY'),
            'base_url' => env('WENXIN_BASE_URL', 'https://aip.baidubce.com'),
            'models' => [
                'ernie-bot-turbo' => [
                    'name' => '文心一言 Turbo',
                    'max_tokens' => 8192,
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
                'ernie-bot' => [
                    'name' => '文心一言',
                    'max_tokens' => 8192,
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
            ],
        ],

        'zhipu' => [
            'driver' => 'zhipu',
            'api_key' => env('ZHIPU_API_KEY'),
            'base_url' => env('ZHIPU_BASE_URL', 'https://open.bigmodel.cn/api/paas/v4'),
            'models' => [
                'glm-4' => [
                    'name' => '智谱GLM-4',
                    'max_tokens' => 128000,
                    'supports_streaming' => true,
                    'supports_functions' => true,
                ],
                'glm-3-turbo' => [
                    'name' => '智谱GLM-3 Turbo',
                    'max_tokens' => 128000,
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
            ],
        ],

        'ollama' => [
            'driver' => 'ollama',
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'models' => [
                'llama2' => [
                    'name' => 'Llama 2',
                    'max_tokens' => 4096,
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
                'codellama' => [
                    'name' => 'Code Llama',
                    'max_tokens' => 4096,
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
                'mistral' => [
                    'name' => 'Mistral',
                    'max_tokens' => 8192,
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring model performance and usage statistics.
    |
    */

    'monitoring' => [
        'enabled' => env('AI_MONITORING_ENABLED', true),
        'log_requests' => env('AI_LOG_REQUESTS', true),
        'track_costs' => env('AI_TRACK_COSTS', true),
        'cache_responses' => env('AI_CACHE_RESPONSES', false),
        'cache_ttl' => env('AI_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limiting AI model requests.
    |
    */

    'rate_limiting' => [
        'enabled' => env('AI_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('AI_REQUESTS_PER_MINUTE', 60),
        'requests_per_hour' => env('AI_REQUESTS_PER_HOUR', 1000),
        'tokens_per_minute' => env('AI_TOKENS_PER_MINUTE', 100000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Load Balancing
    |--------------------------------------------------------------------------
    |
    | Configuration for load balancing across multiple models.
    |
    */

    'load_balancing' => [
        'enabled' => env('AI_LOAD_BALANCING_ENABLED', false),
        'strategy' => env('AI_LOAD_BALANCING_STRATEGY', 'round_robin'), // round_robin, least_cost, fastest_response
        'fallback_enabled' => env('AI_FALLBACK_ENABLED', true),
    ],
];
