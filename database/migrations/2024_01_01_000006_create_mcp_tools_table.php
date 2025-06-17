<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mcp_tools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description');
            $table->string('category'); // builtin, external, custom
            $table->string('type'); // file_operations, web_scraping, api_client, etc.
            $table->string('version')->default('1.0.0');
            $table->json('schema'); // input/output schema
            $table->json('configuration')->nullable();
            $table->string('handler_class')->nullable(); // for builtin tools
            $table->string('executable_path')->nullable(); // for external tools
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('sandbox_enabled')->default(true);
            $table->integer('timeout_seconds')->default(300);
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index(['type', 'is_active']);
            $table->index('last_used_at');
        });

        Schema::create('mcp_tool_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mcp_tool_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('execution_id')->unique();
            $table->json('input_data');
            $table->json('output_data')->nullable();
            $table->string('status')->default('pending'); // pending, running, completed, failed, timeout
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->string('context')->nullable(); // workflow_execution_id, conversation_id, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['mcp_tool_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'started_at']);
            $table->index('execution_id');
            $table->index('context');
        });

        Schema::create('generation_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('task_id')->unique();
            $table->string('type'); // text, image, video, audio
            $table->string('provider'); // openai, anthropic, midjourney, etc.
            $table->string('model')->nullable();
            $table->text('prompt');
            $table->json('parameters')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('result')->nullable();
            $table->string('result_url')->nullable();
            $table->text('error_message')->nullable();
            $table->decimal('cost', 8, 6)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'type', 'status']);
            $table->index(['provider', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generation_tasks');
        Schema::dropIfExists('mcp_tool_executions');
        Schema::dropIfExists('mcp_tools');
    }
};
