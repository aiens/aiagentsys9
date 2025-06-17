<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Conversation extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'settings',
        'message_count',
        'total_tokens',
        'total_cost',
        'is_archived',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'total_cost' => 'decimal:6',
        'is_archived' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'is_archived', 'message_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user that owns this conversation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages in this conversation.
     */
    public function messages()
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('created_at');
    }

    /**
     * Get the latest message in this conversation.
     */
    public function latestMessage()
    {
        return $this->hasOne(ConversationMessage::class)->latestOfMany();
    }

    /**
     * Scope a query to only include active conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope a query to only include archived conversations.
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope a query to order by last message time.
     */
    public function scopeByLastMessage($query)
    {
        return $query->orderBy('last_message_at', 'desc');
    }

    /**
     * Add a message to this conversation.
     */
    public function addMessage(array $messageData): ConversationMessage
    {
        $message = $this->messages()->create(array_merge($messageData, [
            'user_id' => $this->user_id,
        ]));

        $this->updateStats();
        
        return $message;
    }

    /**
     * Update conversation statistics.
     */
    public function updateStats(): void
    {
        $stats = $this->messages()->selectRaw('
            COUNT(*) as message_count,
            SUM(input_tokens + output_tokens) as total_tokens,
            SUM(cost) as total_cost,
            MAX(created_at) as last_message_at
        ')->first();

        $this->update([
            'message_count' => $stats->message_count ?? 0,
            'total_tokens' => $stats->total_tokens ?? 0,
            'total_cost' => $stats->total_cost ?? 0,
            'last_message_at' => $stats->last_message_at,
        ]);
    }

    /**
     * Archive this conversation.
     */
    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    /**
     * Unarchive this conversation.
     */
    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    /**
     * Generate a title for this conversation based on the first message.
     */
    public function generateTitle(): string
    {
        $firstUserMessage = $this->messages()
            ->where('role', 'user')
            ->first();

        if (!$firstUserMessage) {
            return 'New Conversation';
        }

        $content = $firstUserMessage->content;
        $title = substr($content, 0, 50);
        
        if (strlen($content) > 50) {
            $title .= '...';
        }

        return $title;
    }

    /**
     * Get conversation settings with defaults.
     */
    public function getSettingsWithDefaults(): array
    {
        $defaults = [
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'streaming' => true,
            'system_prompt' => null,
        ];

        return array_merge($defaults, $this->settings ?? []);
    }

    /**
     * Get the conversation context for AI models.
     */
    public function getContext(int $maxMessages = 20): array
    {
        return $this->messages()
            ->latest()
            ->take($maxMessages)
            ->get()
            ->reverse()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            })
            ->toArray();
    }

    /**
     * Check if the conversation has any messages.
     */
    public function hasMessages(): bool
    {
        return $this->message_count > 0;
    }

    /**
     * Get the estimated cost per message.
     */
    public function getAverageCostPerMessage(): float
    {
        if ($this->message_count === 0) {
            return 0;
        }

        return $this->total_cost / $this->message_count;
    }

    /**
     * Get the average tokens per message.
     */
    public function getAverageTokensPerMessage(): float
    {
        if ($this->message_count === 0) {
            return 0;
        }

        return $this->total_tokens / $this->message_count;
    }
}
