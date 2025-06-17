# Laravel AI Agent System

一个现代化的AI智能体平台，支持多模型调用、知识管理、工作流编排和多媒体内容生成。

## 技术栈

- **后端框架**: Laravel 11.x
- **前端**: Vue.js 3 + Inertia.js
- **数据库**: MySQL 8.0+ / PostgreSQL
- **缓存**: Redis
- **队列**: Laravel Queue (Redis/Database)
- **文件存储**: Laravel Storage
- **API**: RESTful API + GraphQL (可选)

## 核心功能模块

### 1. 多模型管理系统
- OpenAI GPT系列 (GPT-4, GPT-3.5等)
- Anthropic Claude系列
- Google Gemini
- 国产大模型 (通义千问、文心一言、智谱GLM等)
- 本地部署模型 (Ollama集成)

### 2. 知识库系统
- 文档上传和解析 (PDF, DOCX, TXT, MD等)
- 向量化存储和检索
- 知识图谱构建
- 语义搜索和RAG集成

### 3. 记忆库系统
- 短期记忆 (会话级别)
- 长期记忆 (用户级别)
- 工作记忆 (任务级别)
- 元记忆 (系统级别)

### 4. 工作流引擎
- 可视化拖拽编辑器
- 节点类型: AI调用、条件判断、数据处理、API调用等
- 并行执行和分支控制
- 错误处理和重试机制

### 5. MCP (Model Context Protocol) 工具支持
- 工具注册和发现
- 动态工具调用
- 工具权限管理
- 自定义工具开发框架

### 6. 多媒体生成系统
- 文本生成 (各种大语言模型)
- 图片生成 (DALL-E, Midjourney, Stable Diffusion等)
- 视频生成 (Runway, Pika Labs等)
- 音频生成 (TTS, 音乐生成等)

## 安装和部署

### 环境要求
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+ 或 PostgreSQL 13+
- Redis 6.0+

### 安装步骤

1. 克隆项目
```bash
git clone <repository-url>
cd laravel-ai-agent-system
```

2. 安装依赖
```bash
composer install
npm install
```

3. 环境配置
```bash
cp .env.example .env
php artisan key:generate
```

4. 数据库迁移
```bash
php artisan migrate
php artisan db:seed
```

5. 启动服务
```bash
php artisan serve
npm run dev
```

## 开发进度

- [x] 项目初始化
- [ ] 基础架构搭建
- [ ] 用户认证系统
- [ ] 多模型管理系统
- [ ] 知识库系统
- [ ] 记忆库系统
- [ ] 工作流引擎
- [ ] MCP工具支持
- [ ] 多媒体生成系统

## 贡献指南

请参考 [CONTRIBUTING.md](CONTRIBUTING.md) 了解如何参与项目开发。

## 许可证

本项目采用 MIT 许可证。详情请参考 [LICENSE](LICENSE) 文件。
