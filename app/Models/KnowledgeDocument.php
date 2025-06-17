<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class KnowledgeDocument extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'knowledge_base_id',
        'user_id',
        'title',
        'filename',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'hash',
        'content',
        'metadata',
        'status',
        'error_message',
        'chunk_count',
        'token_count',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'chunk_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the knowledge base that owns this document.
     */
    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    /**
     * Get the user that uploaded this document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the chunks for this document.
     */
    public function chunks()
    {
        return $this->hasMany(KnowledgeChunk::class);
    }

    /**
     * Scope a query to only include documents with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include processed documents.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending documents.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include failed documents.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to filter by file type.
     */
    public function scopeByFileType($query, string $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Check if the document is processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the document is pending processing.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the document processing failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the document as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark the document as completed.
     */
    public function markAsCompleted(int $chunkCount, int $tokenCount): void
    {
        $this->update([
            'status' => 'completed',
            'chunk_count' => $chunkCount,
            'token_count' => $tokenCount,
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark the document as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get the file size in human readable format.
     */
    public function getHumanReadableSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the document's parser class.
     */
    public function getParserClass(): ?string
    {
        $parsers = config('knowledge.document_processing.parsers', []);
        return $parsers[$this->file_type] ?? null;
    }

    /**
     * Check if the document type is supported.
     */
    public function isSupported(): bool
    {
        $supportedFormats = config('knowledge.document_processing.supported_formats', []);
        return in_array($this->file_type, $supportedFormats);
    }

    /**
     * Get the document metadata with defaults.
     */
    public function getMetadataWithDefaults(): array
    {
        $defaults = [
            'language' => 'en',
            'author' => null,
            'created_date' => null,
            'modified_date' => null,
            'page_count' => null,
            'word_count' => null,
        ];

        return array_merge($defaults, $this->metadata ?? []);
    }
}
