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
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('vector_db_type')->default('pinecone'); // pinecone, weaviate, qdrant
            $table->string('vector_db_index')->nullable();
            $table->string('embedding_model')->default('text-embedding-ada-002');
            $table->integer('chunk_size')->default(1000);
            $table->integer('chunk_overlap')->default(200);
            $table->json('settings')->nullable();
            $table->integer('document_count')->default(0);
            $table->integer('chunk_count')->default(0);
            $table->bigInteger('total_tokens')->default(0);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['is_public', 'is_active']);
            $table->index('last_updated_at');
        });

        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('filename');
            $table->string('file_path');
            $table->string('file_type'); // pdf, docx, txt, md, etc.
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->string('hash')->unique(); // for deduplication
            $table->longText('content')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->integer('chunk_count')->default(0);
            $table->integer('token_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['knowledge_base_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('hash');
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_document_id')->constrained()->onDelete('cascade');
            $table->foreignId('knowledge_base_id')->constrained()->onDelete('cascade');
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->string('vector_id')->nullable(); // ID in vector database
            $table->integer('chunk_index');
            $table->integer('start_position');
            $table->integer('end_position');
            $table->integer('token_count');
            $table->decimal('embedding_cost', 8, 6)->default(0);
            $table->timestamps();
            
            $table->index(['knowledge_document_id', 'chunk_index']);
            $table->index(['knowledge_base_id', 'created_at']);
            $table->index('vector_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
        Schema::dropIfExists('knowledge_bases');
    }
};
