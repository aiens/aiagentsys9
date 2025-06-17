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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider'); // openai, anthropic, google, etc.
            $table->string('model_id'); // gpt-4, claude-3-sonnet, etc.
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->json('capabilities'); // streaming, functions, vision, etc.
            $table->json('parameters'); // max_tokens, temperature ranges, etc.
            $table->json('pricing')->nullable(); // cost per token
            $table->integer('max_tokens')->default(4096);
            $table->boolean('supports_streaming')->default(false);
            $table->boolean('supports_functions')->default(false);
            $table->boolean('supports_vision')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0);
            $table->json('rate_limits')->nullable();
            $table->timestamps();
            
            $table->unique(['provider', 'model_id']);
            $table->index(['provider', 'is_active']);
            $table->index(['is_active', 'priority']);
        });

        Schema::create('ai_model_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ai_model_id')->constrained()->onDelete('cascade');
            $table->string('request_id')->unique();
            $table->text('prompt')->nullable();
            $table->longText('response')->nullable();
            $table->json('parameters')->nullable();
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->integer('response_time_ms')->nullable();
            $table->string('status'); // success, error, timeout
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['ai_model_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_model_usage');
        Schema::dropIfExists('ai_models');
    }
};
