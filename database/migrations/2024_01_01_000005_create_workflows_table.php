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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('definition'); // workflow JSON definition
            $table->string('version')->default('1.0.0');
            $table->string('status')->default('draft'); // draft, active, inactive, archived
            $table->json('settings')->nullable();
            $table->json('variables')->nullable(); // default variables
            $table->boolean('is_public')->default(false);
            $table->boolean('is_template')->default(false);
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['is_public', 'is_template']);
            $table->index(['category', 'status']);
            $table->index('last_executed_at');
        });

        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('execution_id')->unique();
            $table->string('status')->default('pending'); // pending, running, completed, failed, cancelled
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->json('variables')->nullable();
            $table->json('context')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('total_nodes')->default(0);
            $table->integer('completed_nodes')->default(0);
            $table->integer('failed_nodes')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->decimal('total_cost', 10, 6)->default(0);
            $table->timestamps();
            
            $table->index(['workflow_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'started_at']);
            $table->index('execution_id');
        });

        Schema::create('workflow_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_execution_id')->constrained()->onDelete('cascade');
            $table->string('node_id');
            $table->string('node_type');
            $table->string('status'); // pending, running, completed, failed, skipped
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->decimal('cost', 8, 6)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['workflow_execution_id', 'node_id']);
            $table->index(['status', 'started_at']);
            $table->index('node_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_execution_logs');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflows');
    }
};
