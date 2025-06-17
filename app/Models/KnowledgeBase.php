<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class KnowledgeBase extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'vector_db_type',
        'vector_db_index',
        'embedding_model',
        'chunk_size',
        'chunk_overlap',
        'settings',
        'document_count',
        'chunk_count',
        'total_tokens',
        'is_public',
        'is_active',
        'last_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'is_public', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user that owns the knowledge base.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the documents in this knowledge base.
     */
    public function documents()
    {
        return $this->hasMany(KnowledgeDocument::class);
    }

    /**
     * Get the chunks in this knowledge base.
     */
    public function chunks()
    {
        return $this->hasMany(KnowledgeChunk::class);
    }

    /**
     * Scope a query to only include active knowledge bases.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include public knowledge bases.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to filter by vector database type.
     */
    public function scopeByVectorDb($query, string $type)
    {
        return $query->where('vector_db_type', $type);
    }

    /**
     * Get the total estimated cost for this knowledge base.
     */
    public function getEstimatedCost(): float
    {
        return $this->chunks()->sum('embedding_cost');
    }

    /**
     * Update the document and chunk counts.
     */
    public function updateCounts(): void
    {
        $this->update([
            'document_count' => $this->documents()->count(),
            'chunk_count' => $this->chunks()->count(),
            'total_tokens' => $this->chunks()->sum('token_count'),
            'last_updated_at' => now(),
        ]);
    }

    /**
     * Get the knowledge base settings with defaults.
     */
    public function getSettingsWithDefaults(): array
    {
        $defaults = [
            'similarity_threshold' => 0.7,
            'max_results' => 10,
            'rerank_enabled' => true,
            'search_strategy' => 'hybrid',
        ];

        return array_merge($defaults, $this->settings ?? []);
    }

    /**
     * Check if the knowledge base is accessible by a user.
     */
    public function isAccessibleBy(User $user): bool
    {
        return $this->user_id === $user->id || 
               ($this->is_public && $this->is_active) ||
               $user->hasPermissionTo('access_all_knowledge_bases');
    }

    /**
     * Get the vector database configuration.
     */
    public function getVectorDbConfig(): array
    {
        $config = config("knowledge.vector_databases.{$this->vector_db_type}", []);
        
        if ($this->vector_db_index) {
            $config['index_name'] = $this->vector_db_index;
        }
        
        return $config;
    }

    /**
     * Get the embedding model configuration.
     */
    public function getEmbeddingConfig(): array
    {
        return config("knowledge.embedding.models.{$this->embedding_model}", []);
    }
}
