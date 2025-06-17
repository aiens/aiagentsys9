<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MemoryStore extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'memory_type',
        'key',
        'value',
        'metadata',
        'context',
        'importance_score',
        'access_count',
        'last_accessed_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'last_accessed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Memory type constants.
     */
    const TYPE_SHORT_TERM = 'short_term';
    const TYPE_LONG_TERM = 'long_term';
    const TYPE_WORKING = 'working';
    const TYPE_META = 'meta';

    /**
     * Get the user that owns this memory.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include memories of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('memory_type', $type);
    }

    /**
     * Scope a query to only include memories in a specific context.
     */
    public function scopeInContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    /**
     * Scope a query to only include non-expired memories.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include expired memories.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to order by importance score.
     */
    public function scopeByImportance($query, string $direction = 'desc')
    {
        return $query->orderBy('importance_score', $direction);
    }

    /**
     * Scope a query to order by access frequency.
     */
    public function scopeByAccessCount($query, string $direction = 'desc')
    {
        return $query->orderBy('access_count', $direction);
    }

    /**
     * Scope a query to order by last access time.
     */
    public function scopeByLastAccess($query, string $direction = 'desc')
    {
        return $query->orderBy('last_accessed_at', $direction);
    }

    /**
     * Store a memory with automatic expiration based on type.
     */
    public static function store(
        int $userId,
        string $type,
        string $key,
        $value,
        ?string $context = null,
        ?array $metadata = null,
        ?int $importanceScore = null
    ): self {
        $expiresAt = static::calculateExpiration($type);
        $importanceScore = $importanceScore ?? static::calculateImportance($type, $value);

        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'memory_type' => $type,
                'key' => $key,
                'context' => $context,
            ],
            [
                'value' => is_string($value) ? $value : json_encode($value),
                'metadata' => $metadata,
                'importance_score' => $importanceScore,
                'expires_at' => $expiresAt,
                'last_accessed_at' => now(),
                'access_count' => 1,
            ]
        );
    }

    /**
     * Retrieve a memory and update access statistics.
     */
    public static function retrieve(
        int $userId,
        string $type,
        string $key,
        ?string $context = null
    ): ?self {
        $memory = static::where('user_id', $userId)
            ->where('memory_type', $type)
            ->where('key', $key)
            ->when($context, fn($q) => $q->where('context', $context))
            ->notExpired()
            ->first();

        if ($memory) {
            $memory->increment('access_count');
            $memory->update(['last_accessed_at' => now()]);
        }

        return $memory;
    }

    /**
     * Get the decoded value.
     */
    public function getDecodedValue()
    {
        $value = $this->value;
        
        if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        }
        
        return $value;
    }

    /**
     * Check if the memory is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Extend the memory expiration.
     */
    public function extend(?Carbon $newExpiration = null): void
    {
        $expiration = $newExpiration ?? static::calculateExpiration($this->memory_type);
        $this->update(['expires_at' => $expiration]);
    }

    /**
     * Increase the importance score.
     */
    public function increaseImportance(int $amount = 1): void
    {
        $this->increment('importance_score', $amount);
    }

    /**
     * Calculate expiration time based on memory type.
     */
    protected static function calculateExpiration(string $type): ?Carbon
    {
        $ttls = [
            self::TYPE_SHORT_TERM => config('ai_models.memory.short_term_ttl', 3600), // 1 hour
            self::TYPE_LONG_TERM => config('ai_models.memory.long_term_ttl', 2592000), // 30 days
            self::TYPE_WORKING => config('ai_models.memory.working_ttl', 86400), // 1 day
            self::TYPE_META => null, // Never expires
        ];

        $ttl = $ttls[$type] ?? null;
        
        return $ttl ? now()->addSeconds($ttl) : null;
    }

    /**
     * Calculate importance score based on type and content.
     */
    protected static function calculateImportance(string $type, $value): int
    {
        $baseScores = [
            self::TYPE_SHORT_TERM => 1,
            self::TYPE_LONG_TERM => 5,
            self::TYPE_WORKING => 3,
            self::TYPE_META => 10,
        ];

        $score = $baseScores[$type] ?? 1;

        // Increase score based on content length (longer content might be more important)
        if (is_string($value)) {
            $length = strlen($value);
            if ($length > 1000) $score += 2;
            elseif ($length > 500) $score += 1;
        }

        return $score;
    }

    /**
     * Clean up expired memories.
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }

    /**
     * Clean up least important memories when storage limit is reached.
     */
    public static function cleanupByImportance(int $userId, int $limit = 1000): int
    {
        $count = static::where('user_id', $userId)->count();
        
        if ($count <= $limit) {
            return 0;
        }

        $toDelete = $count - $limit;
        
        $memories = static::where('user_id', $userId)
            ->orderBy('importance_score')
            ->orderBy('last_accessed_at')
            ->take($toDelete)
            ->pluck('id');

        return static::whereIn('id', $memories)->delete();
    }
}
