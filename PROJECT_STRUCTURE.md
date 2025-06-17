# 📁 Project Structure

This document provides a comprehensive overview of the AI Agent System project structure, explaining the purpose and organization of each directory and key files.

## 🏗️ Root Directory Structure

```
aiagentsys9/
├── app/                    # Laravel application core
├── bootstrap/              # Application bootstrap files
├── config/                 # Configuration files
├── database/              # Database migrations, seeders, factories
├── public/                # Public web files (entry point)
├── resources/             # Frontend resources (Vue.js, CSS, etc.)
├── routes/                # Application routes
├── storage/               # Application storage (logs, cache, uploads)
├── tests/                 # Test files
├── vendor/                # Composer dependencies
├── .env.example           # Environment variables template
├── composer.json          # PHP dependencies
├── package.json           # Node.js dependencies
├── vite.config.js         # Vite build configuration
├── tailwind.config.js     # Tailwind CSS configuration
└── README.md              # Project documentation
```

## 📱 Application Directory (`app/`)

### Core Structure
```
app/
├── Console/               # Artisan commands
├── Exceptions/            # Exception handling
├── Http/                  # HTTP layer (controllers, middleware, requests)
├── Models/                # Eloquent models
├── Providers/             # Service providers
└── Services/              # Business logic services
```

### HTTP Layer (`app/Http/`)
```
Http/
├── Controllers/
│   ├── Api/               # API controllers
│   │   ├── AiModelController.php
│   │   ├── ConversationController.php
│   │   ├── KnowledgeBaseController.php
│   │   ├── WorkflowController.php
│   │   ├── MemoryController.php
│   │   └── McpToolController.php
│   └── Controller.php     # Base controller
├── Middleware/            # HTTP middleware
├── Requests/              # Form request validation
└── Resources/             # API resources
```

### Models (`app/Models/`)
```
Models/
├── User.php               # User model
├── AiModel.php            # AI model configuration
├── AiModelUsage.php       # Usage tracking
├── Conversation.php       # Chat conversations
├── ConversationMessage.php # Chat messages
├── KnowledgeBase.php      # Knowledge base
├── KnowledgeDocument.php  # Documents
├── KnowledgeChunk.php     # Document chunks
├── Workflow.php           # Workflow definitions
├── WorkflowExecution.php  # Workflow runs
├── WorkflowExecutionLog.php # Execution logs
├── MemoryStore.php        # Memory storage
├── McpTool.php            # MCP tools
├── McpToolExecution.php   # Tool executions
├── GenerationTask.php     # Generation tasks
└── UserPreference.php     # User preferences
```

### Services (`app/Services/`)
```
Services/
├── AiModelService.php     # AI model management
├── ConversationService.php # Chat functionality
├── KnowledgeBaseService.php # Knowledge management
├── EmbeddingService.php   # Vector embeddings
├── VectorDatabaseService.php # Vector DB operations
├── MemoryService.php      # Memory management
└── WorkflowService.php    # Workflow execution
```

## 🗄️ Database Directory (`database/`)

### Structure
```
database/
├── factories/             # Model factories for testing
├── migrations/            # Database schema migrations
│   ├── 2024_01_01_000001_create_users_table.php
│   ├── 2024_01_01_000002_create_ai_models_table.php
│   ├── 2024_01_01_000003_create_conversations_table.php
│   ├── 2024_01_01_000004_create_knowledge_bases_table.php
│   ├── 2024_01_01_000005_create_workflows_table.php
│   ├── 2024_01_01_000006_create_memory_stores_table.php
│   └── ...
└── seeders/               # Database seeders
    ├── DatabaseSeeder.php
    ├── AiModelSeeder.php
    ├── UserSeeder.php
    └── ...
```

### Key Migrations
- **Users & Authentication**: User management and preferences
- **AI Models**: Model configurations and usage tracking
- **Conversations**: Chat system with messages and context
- **Knowledge Base**: Document storage and vector indexing
- **Workflows**: Visual workflow definitions and executions
- **Memory**: Multi-type memory storage system
- **MCP Tools**: Tool definitions and execution logs

## 🎨 Resources Directory (`resources/`)

### Structure
```
resources/
├── css/
│   └── app.css            # Main CSS file with Tailwind
├── js/
│   ├── Components/        # Vue.js components
│   ├── Layouts/           # Application layouts
│   ├── Pages/             # Inertia.js pages
│   ├── Plugins/           # Vue plugins
│   ├── Stores/            # Pinia stores
│   ├── app.js             # Main JavaScript entry
│   └── bootstrap.js       # Bootstrap configuration
└── views/                 # Blade templates (minimal usage)
```

### Frontend Components (`resources/js/Components/`)
```
Components/
├── Common/                # Reusable UI components
│   ├── Button.vue
│   ├── Input.vue
│   ├── Modal.vue
│   ├── Pagination.vue
│   └── ...
├── Navigation/            # Navigation components
│   ├── NavLink.vue
│   ├── MobileNavLink.vue
│   └── Sidebar.vue
├── Chat/                  # Chat-specific components
│   ├── MessageBubble.vue
│   ├── MessageInput.vue
│   └── ConversationList.vue
├── Knowledge/             # Knowledge base components
│   ├── DocumentUpload.vue
│   ├── SearchInterface.vue
│   └── DocumentViewer.vue
├── Workflow/              # Workflow designer components
│   ├── WorkflowCanvas.vue
│   ├── NodePalette.vue
│   ├── WorkflowNode.vue
│   └── ConnectionLine.vue
├── Memory/                # Memory management components
│   ├── MemoryList.vue
│   ├── MemoryItem.vue
│   └── MemorySearch.vue
└── Toast/                 # Notification system
    ├── ToastContainer.vue
    └── ToastItem.vue
```

### Pages (`resources/js/Pages/`)
```
Pages/
├── Auth/                  # Authentication pages
│   ├── Login.vue
│   ├── Register.vue
│   └── ForgotPassword.vue
├── Dashboard/             # Dashboard pages
│   └── Index.vue
├── Conversations/         # Chat pages
│   ├── Index.vue
│   ├── Chat.vue
│   └── Settings.vue
├── Knowledge/             # Knowledge base pages
│   ├── Index.vue
│   ├── Show.vue
│   ├── Documents.vue
│   └── Search.vue
├── Workflows/             # Workflow pages
│   ├── Index.vue
│   ├── Designer.vue
│   ├── Execution.vue
│   └── Templates.vue
├── Memory/                # Memory pages
│   ├── Index.vue
│   ├── Search.vue
│   └── Statistics.vue
├── Tools/                 # MCP tools pages
│   ├── Index.vue
│   ├── Marketplace.vue
│   └── Executions.vue
└── Profile/               # User profile pages
    ├── Show.vue
    ├── Edit.vue
    └── Preferences.vue
```

## ⚙️ Configuration Directory (`config/`)

### Key Configuration Files
```
config/
├── app.php                # Application configuration
├── database.php           # Database connections
├── cache.php              # Cache configuration
├── queue.php              # Queue configuration
├── ai_models.php          # AI model providers
├── knowledge.php          # Knowledge base settings
├── memory.php             # Memory system settings
├── workflows.php          # Workflow engine settings
└── mcp_tools.php          # MCP tool configuration
```

### Custom Configuration Files

#### `config/ai_models.php`
```php
return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'base_url' => 'https://api.openai.com/v1',
        ],
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'base_url' => 'https://api.anthropic.com',
        ],
        // ... other providers
    ],
];
```

#### `config/knowledge.php`
```php
return [
    'default_vector_db' => env('VECTOR_DB_DEFAULT', 'pinecone'),
    'document_processing' => [
        'chunk_size' => 1000,
        'chunk_overlap' => 200,
        'max_file_size' => 50 * 1024 * 1024, // 50MB
        'supported_formats' => ['pdf', 'docx', 'txt', 'md'],
    ],
    'embedding' => [
        'default_model' => 'text-embedding-ada-002',
        'batch_size' => 100,
    ],
];
```

## 🛣️ Routes Directory (`routes/`)

### Route Files
```
routes/
├── web.php                # Web routes (minimal, mostly for auth)
├── api.php                # API routes (main application routes)
└── console.php            # Artisan command routes
```

### API Route Structure
```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    // AI Models
    Route::prefix('ai-models')->group(function () {
        Route::get('/', [AiModelController::class, 'index']);
        Route::post('/call', [AiModelController::class, 'call']);
        Route::post('/stream', [AiModelController::class, 'stream']);
    });
    
    // Conversations
    Route::apiResource('conversations', ConversationController::class);
    Route::post('conversations/{id}/messages', [ConversationController::class, 'sendMessage']);
    
    // Knowledge Bases
    Route::apiResource('knowledge-bases', KnowledgeBaseController::class);
    Route::post('knowledge-bases/{id}/documents', [KnowledgeBaseController::class, 'uploadDocument']);
    
    // Workflows
    Route::apiResource('workflows', WorkflowController::class);
    Route::post('workflows/{id}/execute', [WorkflowController::class, 'execute']);
    
    // Memory
    Route::apiResource('memory', MemoryController::class);
    Route::post('memory/search', [MemoryController::class, 'search']);
    
    // MCP Tools
    Route::apiResource('mcp-tools', McpToolController::class);
    Route::post('mcp-tools/{id}/execute', [McpToolController::class, 'execute']);
});
```

## 🧪 Tests Directory (`tests/`)

### Test Structure
```
tests/
├── Feature/               # Feature tests (HTTP tests)
│   ├── AiModelTest.php
│   ├── ConversationTest.php
│   ├── KnowledgeBaseTest.php
│   ├── WorkflowTest.php
│   └── ...
├── Unit/                  # Unit tests (isolated tests)
│   ├── Services/
│   │   ├── AiModelServiceTest.php
│   │   ├── ConversationServiceTest.php
│   │   └── ...
│   └── Models/
│       ├── UserTest.php
│       ├── ConversationTest.php
│       └── ...
└── TestCase.php           # Base test case
```

## 📦 Package Configuration

### Composer Dependencies (`composer.json`)
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "inertiajs/inertia-laravel": "^1.0",
        "guzzlehttp/guzzle": "^7.2",
        "predis/predis": "^2.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.1",
        "mockery/mockery": "^1.4.4",
        "laravel/pint": "^1.0"
    }
}
```

### NPM Dependencies (`package.json`)
```json
{
    "devDependencies": {
        "@inertiajs/vue3": "^1.0.0",
        "@vitejs/plugin-vue": "^4.0.0",
        "vue": "^3.2.31",
        "vite": "^4.0.0",
        "@tailwindcss/forms": "^0.5.3",
        "@tailwindcss/typography": "^0.5.9",
        "tailwindcss": "^3.2.0",
        "@heroicons/vue": "^2.0.0",
        "pinia": "^2.0.0"
    }
}
```

## 🔧 Build Configuration

### Vite Configuration (`vite.config.js`)
- Vue.js 3 plugin configuration
- Laravel plugin for asset compilation
- Code splitting for optimal loading
- Development server configuration

### Tailwind Configuration (`tailwind.config.js`)
- Custom color palette
- Component classes
- Typography plugin
- Forms plugin
- Responsive breakpoints

## 📝 Documentation Files

- `README.md` - Main project documentation (Chinese)
- `README_EN.md` - English documentation
- `DEPLOYMENT.md` - Deployment guide
- `PROJECT_STRUCTURE.md` - This file
- `CHANGELOG.md` - Version history
- `CONTRIBUTING.md` - Contribution guidelines

## 🔒 Security Files

- `.env.example` - Environment variables template
- `.gitignore` - Git ignore rules
- `composer.lock` - Locked PHP dependencies
- `package-lock.json` - Locked Node.js dependencies

---

This structure provides a scalable, maintainable architecture that follows Laravel and Vue.js best practices while supporting the complex requirements of an AI agent system.
