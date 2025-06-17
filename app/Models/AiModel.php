<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AiModel extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'provider',
        'model_id',
        'display_name',
        'description',
        'capabilities',
        'parameters',
        'pricing',
        'max_tokens',
        'supports_streaming',
        'supports_functions',
        'supports_vision',
        'is_active',
        'is_default',
        'priority',
        'rate_limits',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capabilities' => 'array',
        'parameters' => 'array',
        'pricing' => 'array',
        'rate_limits' => 'array',
        'supports_streaming' => 'boolean',
        'supports_functions' => 'boolean',
        'supports_vision' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'provider', 'model_id', 'is_active', 'is_default'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the model's usage records.
     */
    public function usage()
    {
        return $this->hasMany(AiModelUsage::class);
    }

    /**
     * Get the model's conversation messages.
     */
    public function conversationMessages()
    {
        return $this->hasMany(ConversationMessage::class);
    }

    /**
     * Scope a query to only include active models.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include models by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope a query to only include models that support streaming.
     */
    public function scopeSupportsStreaming($query)
    {
        return $query->where('supports_streaming', true);
    }

    /**
     * Scope a query to only include models that support functions.
     */
    public function scopeSupportsFunctions($query)
    {
        return $query->where('supports_functions', true);
    }

    /**
     * Scope a query to only include models that support vision.
     */
    public function scopeSupportsVision($query)
    {
        return $query->where('supports_vision', true);
    }

    /**
     * Get the default model for a provider.
     */
    public static function getDefaultForProvider(string $provider): ?self
    {
        return static::where('provider', $provider)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get the cost per token for input.
     */
    public function getInputCostPerToken(): float
    {
        return $this->pricing['input'] ?? 0;
    }

    /**
     * Get the cost per token for output.
     */
    public function getOutputCostPerToken(): float
    {
        return $this->pricing['output'] ?? 0;
    }

    /**
     * Calculate the cost for a request.
     */
    public function calculateCost(int $inputTokens, int $outputTokens): float
    {
        $inputCost = ($inputTokens / 1000) * $this->getInputCostPerToken();
        $outputCost = ($outputTokens / 1000) * $this->getOutputCostPerToken();
        
        return round($inputCost + $outputCost, 6);
    }

    /**
     * Check if the model supports a specific capability.
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    /**
     * Get the model's rate limit for a specific type.
     */
    public function getRateLimit(string $type): ?int
    {
        return $this->rate_limits[$type] ?? null;
    }

    /**
     * Get the model's full identifier.
     */
    public function getFullIdentifier(): string
    {
        return "{$this->provider}:{$this->model_id}";
    }
}
