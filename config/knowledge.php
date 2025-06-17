<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Vector Database
    |--------------------------------------------------------------------------
    |
    | This option controls the default vector database that will be used
    | for storing and retrieving knowledge embeddings.
    |
    */

    'default_vector_db' => env('VECTOR_DB_DEFAULT', 'pinecone'),

    /*
    |--------------------------------------------------------------------------
    | Vector Database Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the vector database connections for your
    | application. Multiple connections are supported for different
    | knowledge bases or use cases.
    |
    */

    'vector_databases' => [
        'pinecone' => [
            'driver' => 'pinecone',
            'api_key' => env('PINECONE_API_KEY'),
            'environment' => env('PINECONE_ENVIRONMENT'),
            'index_name' => env('PINECONE_INDEX_NAME', 'knowledge-base'),
            'dimension' => env('PINECONE_DIMENSION', 1536),
            'metric' => env('PINECONE_METRIC', 'cosine'),
        ],

        'weaviate' => [
            'driver' => 'weaviate',
            'url' => env('WEAVIATE_URL', 'http://localhost:8080'),
            'api_key' => env('WEAVIATE_API_KEY'),
            'class_name' => env('WEAVIATE_CLASS_NAME', 'KnowledgeChunk'),
            'dimension' => env('WEAVIATE_DIMENSION', 1536),
        ],

        'qdrant' => [
            'driver' => 'qdrant',
            'url' => env('QDRANT_URL', 'http://localhost:6333'),
            'api_key' => env('QDRANT_API_KEY'),
            'collection_name' => env('QDRANT_COLLECTION_NAME', 'knowledge_base'),
            'dimension' => env('QDRANT_DIMENSION', 1536),
        ],

        'elasticsearch' => [
            'driver' => 'elasticsearch',
            'hosts' => [env('ELASTICSEARCH_HOST', 'localhost:9200')],
            'username' => env('ELASTICSEARCH_USERNAME'),
            'password' => env('ELASTICSEARCH_PASSWORD'),
            'index_name' => env('ELASTICSEARCH_INDEX_NAME', 'knowledge_base'),
            'dimension' => env('ELASTICSEARCH_DIMENSION', 1536),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for document parsing and processing.
    |
    */

    'document_processing' => [
        'supported_formats' => ['pdf', 'docx', 'txt', 'md', 'html', 'csv', 'json'],
        'max_file_size' => env('KNOWLEDGE_MAX_FILE_SIZE', 50 * 1024 * 1024), // 50MB
        'chunk_size' => env('KNOWLEDGE_CHUNK_SIZE', 1000),
        'chunk_overlap' => env('KNOWLEDGE_CHUNK_OVERLAP', 200),
        'min_chunk_size' => env('KNOWLEDGE_MIN_CHUNK_SIZE', 100),
        'max_chunk_size' => env('KNOWLEDGE_MAX_CHUNK_SIZE', 2000),
        
        'parsers' => [
            'pdf' => \App\Services\Knowledge\Parsers\PdfParser::class,
            'docx' => \App\Services\Knowledge\Parsers\DocxParser::class,
            'txt' => \App\Services\Knowledge\Parsers\TextParser::class,
            'md' => \App\Services\Knowledge\Parsers\MarkdownParser::class,
            'html' => \App\Services\Knowledge\Parsers\HtmlParser::class,
            'csv' => \App\Services\Knowledge\Parsers\CsvParser::class,
            'json' => \App\Services\Knowledge\Parsers\JsonParser::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for text embedding generation.
    |
    */

    'embedding' => [
        'default_model' => env('KNOWLEDGE_EMBEDDING_MODEL', 'text-embedding-ada-002'),
        'batch_size' => env('KNOWLEDGE_EMBEDDING_BATCH_SIZE', 100),
        'max_retries' => env('KNOWLEDGE_EMBEDDING_MAX_RETRIES', 3),
        'retry_delay' => env('KNOWLEDGE_EMBEDDING_RETRY_DELAY', 1000), // milliseconds
        
        'models' => [
            'text-embedding-ada-002' => [
                'provider' => 'openai',
                'dimension' => 1536,
                'max_tokens' => 8191,
                'cost_per_1k_tokens' => 0.0001,
            ],
            'text-embedding-3-small' => [
                'provider' => 'openai',
                'dimension' => 1536,
                'max_tokens' => 8191,
                'cost_per_1k_tokens' => 0.00002,
            ],
            'text-embedding-3-large' => [
                'provider' => 'openai',
                'dimension' => 3072,
                'max_tokens' => 8191,
                'cost_per_1k_tokens' => 0.00013,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retrieval Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for knowledge retrieval and RAG.
    |
    */

    'retrieval' => [
        'default_top_k' => env('KNOWLEDGE_DEFAULT_TOP_K', 5),
        'max_top_k' => env('KNOWLEDGE_MAX_TOP_K', 20),
        'similarity_threshold' => env('KNOWLEDGE_SIMILARITY_THRESHOLD', 0.7),
        'rerank_enabled' => env('KNOWLEDGE_RERANK_ENABLED', true),
        'rerank_top_k' => env('KNOWLEDGE_RERANK_TOP_K', 10),
        
        'search_strategies' => [
            'semantic' => \App\Services\Knowledge\Retrieval\SemanticSearch::class,
            'keyword' => \App\Services\Knowledge\Retrieval\KeywordSearch::class,
            'hybrid' => \App\Services\Knowledge\Retrieval\HybridSearch::class,
        ],
        
        'rerankers' => [
            'cross_encoder' => \App\Services\Knowledge\Rerankers\CrossEncoderReranker::class,
            'bm25' => \App\Services\Knowledge\Rerankers\BM25Reranker::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge Graph Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for knowledge graph construction and querying.
    |
    */

    'knowledge_graph' => [
        'enabled' => env('KNOWLEDGE_GRAPH_ENABLED', false),
        'neo4j_uri' => env('NEO4J_URI', 'bolt://localhost:7687'),
        'neo4j_username' => env('NEO4J_USERNAME', 'neo4j'),
        'neo4j_password' => env('NEO4J_PASSWORD'),
        
        'entity_extraction' => [
            'enabled' => env('KNOWLEDGE_ENTITY_EXTRACTION_ENABLED', true),
            'model' => env('KNOWLEDGE_ENTITY_EXTRACTION_MODEL', 'gpt-3.5-turbo'),
            'batch_size' => env('KNOWLEDGE_ENTITY_EXTRACTION_BATCH_SIZE', 10),
        ],
        
        'relationship_extraction' => [
            'enabled' => env('KNOWLEDGE_RELATIONSHIP_EXTRACTION_ENABLED', true),
            'model' => env('KNOWLEDGE_RELATIONSHIP_EXTRACTION_MODEL', 'gpt-3.5-turbo'),
            'batch_size' => env('KNOWLEDGE_RELATIONSHIP_EXTRACTION_BATCH_SIZE', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching knowledge base operations.
    |
    */

    'caching' => [
        'enabled' => env('KNOWLEDGE_CACHING_ENABLED', true),
        'ttl' => env('KNOWLEDGE_CACHE_TTL', 3600), // 1 hour
        'embeddings_ttl' => env('KNOWLEDGE_EMBEDDINGS_CACHE_TTL', 86400), // 24 hours
        'search_results_ttl' => env('KNOWLEDGE_SEARCH_CACHE_TTL', 1800), // 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Versioning Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for knowledge base versioning and updates.
    |
    */

    'versioning' => [
        'enabled' => env('KNOWLEDGE_VERSIONING_ENABLED', true),
        'max_versions' => env('KNOWLEDGE_MAX_VERSIONS', 10),
        'auto_backup' => env('KNOWLEDGE_AUTO_BACKUP', true),
        'backup_interval' => env('KNOWLEDGE_BACKUP_INTERVAL', 86400), // 24 hours
    ],
];
