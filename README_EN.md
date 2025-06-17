# 🤖 AI Agent System

A comprehensive, enterprise-grade AI agent platform built with Laravel 11.x and Vue.js 3, featuring multi-model AI integration, advanced knowledge management, visual workflow automation, and intelligent memory systems.

![AI Agent System](https://img.shields.io/badge/Laravel-11.x-red?style=for-the-badge&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green?style=for-the-badge&logo=vue.js)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue?style=for-the-badge&logo=php)
![License](https://img.shields.io/badge/License-MIT-yellow?style=for-the-badge)

## 🌟 Features

### 🧠 Multi-Model AI Integration
- **6+ AI Providers**: OpenAI, Anthropic, Google, Chinese models (Qwen, GLM, Baichuan), Local models (Ollama)
- **Streaming Responses**: Real-time character-by-character AI responses
- **Cost Tracking**: Comprehensive token usage and cost monitoring
- **Load Balancing**: Automatic failover and provider switching
- **Rate Limiting**: Intelligent request throttling

### 📚 Knowledge Base Management
- **Multi-Format Support**: PDF, DOCX, TXT, Markdown, and more
- **Vector Databases**: Pinecone, Weaviate, Qdrant, Elasticsearch integration
- **RAG Pipeline**: Retrieval-Augmented Generation with reranking
- **Chunking Strategy**: Intelligent document segmentation
- **Access Control**: Public/private knowledge bases with permissions

### 💬 Advanced Conversation System
- **Real-time Chat**: WebSocket-powered instant messaging
- **Message History**: Infinite scroll with search capabilities
- **Context Management**: Intelligent conversation context handling
- **Export/Share**: Conversation export and sharing functionality
- **Multi-Model**: Switch AI models mid-conversation

### 🔄 Visual Workflow Engine
- **Drag-and-Drop Designer**: Intuitive visual workflow creation
- **12+ Node Types**: AI calls, data transforms, conditions, loops, tools
- **Real-time Validation**: Live error checking and suggestions
- **Template System**: Shareable workflow templates
- **Execution Monitoring**: Real-time progress tracking

### 🧠 Intelligent Memory System
- **4 Memory Types**: Short-term, Long-term, Working, Meta memory
- **Auto-Consolidation**: Important memories automatically promoted
- **Context-Aware**: Retrieval based on conversation context
- **Importance Scoring**: AI-driven memory prioritization
- **Cleanup Automation**: Automatic expiration and maintenance

### 🛠️ MCP Tool Integration
- **Protocol Support**: Model Context Protocol implementation
- **Tool Marketplace**: Extensible tool ecosystem
- **Sandbox Execution**: Secure tool execution environment
- **Permission System**: Granular tool access control
- **Audit Logging**: Complete tool usage tracking

## 🏗️ Architecture

### Backend (Laravel 11.x)
```
├── Models/              # Eloquent models with business logic
├── Services/            # Core business logic services
├── Controllers/         # RESTful API controllers
├── Migrations/          # Database schema definitions
└── Config/             # System configuration files
```

### Frontend (Vue.js 3)
```
├── Pages/              # Inertia.js page components
├── Components/         # Reusable Vue components
├── Layouts/            # Application layouts
├── Plugins/            # Vue plugins (toast, modal, etc.)
└── Stores/             # Pinia state management
```

### Database Schema
- **15+ Tables**: Users, Conversations, Knowledge Bases, Workflows, Memory, etc.
- **Optimized Indexes**: Strategic indexing for performance
- **Relationships**: Comprehensive foreign key relationships
- **Migrations**: Version-controlled schema changes

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Node.js 18+
- MySQL 8.0+ or PostgreSQL 13+
- Redis 6.0+
- Composer 2.0+

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/aiens/aiagentsys9.git
cd aiagentsys9
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database setup**
```bash
php artisan migrate
php artisan db:seed
```

5. **Build assets**
```bash
npm run build
```

6. **Start the application**
```bash
php artisan serve
npm run dev
```

Visit `http://localhost:8000` to access the application.

## ⚙️ Configuration

### AI Model Providers
```env
# OpenAI
OPENAI_API_KEY=your_openai_api_key
OPENAI_ORGANIZATION=your_org_id

# Anthropic
ANTHROPIC_API_KEY=your_anthropic_api_key

# Google AI
GOOGLE_AI_API_KEY=your_google_ai_key

# Chinese Models
QWEN_API_KEY=your_qwen_key
GLM_API_KEY=your_glm_key
BAICHUAN_API_KEY=your_baichuan_key

# Local Models
OLLAMA_BASE_URL=http://localhost:11434
```

### Vector Databases
```env
# Pinecone
PINECONE_API_KEY=your_pinecone_key
PINECONE_ENVIRONMENT=your_environment

# Weaviate
WEAVIATE_URL=http://localhost:8080
WEAVIATE_API_KEY=your_weaviate_key

# Qdrant
QDRANT_URL=http://localhost:6333
QDRANT_API_KEY=your_qdrant_key
```

## 📖 Usage Guide

### 1. Creating Your First Conversation
1. Navigate to **Conversations** → **New Conversation**
2. Select your preferred AI model
3. Start chatting with intelligent context management
4. Export or share conversations as needed

### 2. Building a Knowledge Base
1. Go to **Knowledge** → **New Knowledge Base**
2. Choose your vector database type
3. Upload documents (PDF, DOCX, TXT, etc.)
4. Wait for processing and indexing
5. Search and retrieve information

### 3. Designing Workflows
1. Visit **Workflows** → **New Workflow**
2. Drag nodes from the palette to the canvas
3. Connect nodes to create your logic flow
4. Configure node properties
5. Test and save your workflow

### 4. Managing Memory
1. Access **Memory** section
2. View different memory types
3. Search and filter memories
4. Manage importance scores
5. Consolidate memories as needed

## 🧪 Testing

### Running Tests
```bash
# PHP Unit Tests
php artisan test

# Frontend Tests
npm run test

# E2E Tests
npm run test:e2e
```

## 🚢 Deployment

### Docker Deployment
```bash
# Build and run with Docker Compose
docker-compose up -d

# Or build custom image
docker build -t ai-agent-system .
docker run -p 8000:8000 ai-agent-system
```

### Production Setup
1. **Environment**: Set `APP_ENV=production`
2. **Database**: Configure production database
3. **Cache**: Enable Redis caching
4. **Queue**: Set up queue workers
5. **SSL**: Configure HTTPS
6. **Monitoring**: Set up error tracking

## 🔧 Development

### Code Style
```bash
# PHP CS Fixer
./vendor/bin/php-cs-fixer fix

# ESLint for JavaScript
npm run lint

# Prettier for formatting
npm run format
```

### Database
```bash
# Create migration
php artisan make:migration create_example_table

# Create model
php artisan make:model Example

# Create seeder
php artisan make:seeder ExampleSeeder
```

### Frontend Development
```bash
# Development server with HMR
npm run dev

# Build for production
npm run build

# Analyze bundle
npm run analyze
```

## 📊 Project Statistics

- **Total Files**: 80+ files
- **Lines of Code**: 25,000+ lines
- **API Endpoints**: 100+ endpoints
- **Database Tables**: 15+ tables
- **Vue Components**: 30+ components
- **Service Classes**: 7 core services
- **Model Classes**: 15+ business models

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

- Laravel Framework
- Vue.js Community
- Tailwind CSS
- Inertia.js
- All AI providers and vector database providers

---

**Built with ❤️ by the AI Agent System Team**
