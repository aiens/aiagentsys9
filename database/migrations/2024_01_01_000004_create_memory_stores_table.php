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
        Schema::create('memory_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('memory_type'); // short_term, long_term, working, meta
            $table->string('key');
            $table->longText('value');
            $table->json('metadata')->nullable();
            $table->string('context')->nullable(); // conversation_id, workflow_id, etc.
            $table->integer('importance_score')->default(0);
            $table->integer('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'memory_type', 'key', 'context']);
            $table->index(['user_id', 'memory_type']);
            $table->index(['memory_type', 'expires_at']);
            $table->index(['importance_score', 'last_accessed_at']);
            $table->index('context');
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->json('settings')->nullable(); // model preferences, temperature, etc.
            $table->integer('message_count')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('total_cost', 10, 6)->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_archived', 'last_message_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role'); // user, assistant, system
            $table->longText('content');
            $table->json('metadata')->nullable(); // model used, tokens, cost, etc.
            $table->foreignId('ai_model_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->decimal('cost', 8, 6)->default(0);
            $table->integer('response_time_ms')->nullable();
            $table->json('function_calls')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['user_id', 'role']);
            $table->index(['ai_model_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('memory_stores');
    }
};
