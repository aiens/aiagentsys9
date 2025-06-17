<?php

namespace App\Services;

use App\Models\MemoryStore;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MemoryService
{
    /**
     * Store a memory.
     */
    public function store(
        User $user,
        string $type,
        string $key,
        $value,
        ?string $context = null,
        array $options = []
    ): MemoryStore {
        $memory = MemoryStore::store(
            $user->id,
            $type,
            $key,
            $value,
            $context,
            $options['metadata'] ?? null,
            $options['importance'] ?? null
        );

        Log::debug('Memory stored', [
            'user_id' => $user->id,
            'type' => $type,
            'key' => $key,
            'context' => $context,
        ]);

        return $memory;
    }

    /**
     * Retrieve a specific memory.
     */
    public function retrieve(
        User $user,
        string $type,
        string $key,
        ?string $context = null
    ): ?MemoryStore {
        return MemoryStore::retrieve($user->id, $type, $key, $context);
    }

    /**
     * Retrieve memories by type.
     */
    public function getByType(User $user, string $type, int $limit = 100): Collection
    {
        return MemoryStore::where('user_id', $user->id)
            ->ofType($type)
            ->notExpired()
            ->byImportance()
            ->take($limit)
            ->get();
    }

    /**
     * Retrieve memories by context.
     */
    public function getByContext(User $user, string $context, int $limit = 50): Collection
    {
        return MemoryStore::where('user_id', $user->id)
            ->inContext($context)
            ->notExpired()
            ->byLastAccess()
            ->take($limit)
            ->get();
    }

    /**
     * Search memories by content.
     */
    public function search(User $user, string $query, array $options = []): Collection
    {
        $types = $options['types'] ?? null;
        $context = $options['context'] ?? null;
        $limit = $options['limit'] ?? 50;

        $queryBuilder = MemoryStore::where('user_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('key', 'like', "%{$query}%")
                  ->orWhere('value', 'like', "%{$query}%");
            })
            ->notExpired();

        if ($types) {
            $queryBuilder->whereIn('memory_type', (array) $types);
        }

        if ($context) {
            $queryBuilder->inContext($context);
        }

        return $queryBuilder->byImportance()
            ->take($limit)
            ->get();
    }

    /**
     * Retrieve relevant memories for a query.
     */
    public function retrieveRelevantMemories(
        User $user,
        string $query,
        ?string $context = null,
        int $limit = 10
    ): array {
        // Get memories from different types with different priorities
        $memories = [];

        // Get meta memories (highest priority)
        $metaMemories = $this->getByType($user, MemoryStore::TYPE_META, 5);
        foreach ($metaMemories as $memory) {
            if ($this->isRelevant($memory, $query)) {
                $memories[] = [
                    'type' => $memory->memory_type,
                    'key' => $memory->key,
                    'value' => $memory->getDecodedValue(),
                    'importance' => $memory->importance_score,
                    'relevance' => $this->calculateRelevance($memory, $query),
                ];
            }
        }

        // Get long-term memories
        $longTermMemories = $this->getByType($user, MemoryStore::TYPE_LONG_TERM, 10);
        foreach ($longTermMemories as $memory) {
            if ($this->isRelevant($memory, $query)) {
                $memories[] = [
                    'type' => $memory->memory_type,
                    'key' => $memory->key,
                    'value' => $memory->getDecodedValue(),
                    'importance' => $memory->importance_score,
                    'relevance' => $this->calculateRelevance($memory, $query),
                ];
            }
        }

        // Get context-specific memories
        if ($context) {
            $contextMemories = $this->getByContext($user, $context, 10);
            foreach ($contextMemories as $memory) {
                if ($this->isRelevant($memory, $query)) {
                    $memories[] = [
                        'type' => $memory->memory_type,
                        'key' => $memory->key,
                        'value' => $memory->getDecodedValue(),
                        'importance' => $memory->importance_score,
                        'relevance' => $this->calculateRelevance($memory, $query),
                    ];
                }
            }
        }

        // Get working memories
        $workingMemories = $this->getByType($user, MemoryStore::TYPE_WORKING, 5);
        foreach ($workingMemories as $memory) {
            if ($this->isRelevant($memory, $query)) {
                $memories[] = [
                    'type' => $memory->memory_type,
                    'key' => $memory->key,
                    'value' => $memory->getDecodedValue(),
                    'importance' => $memory->importance_score,
                    'relevance' => $this->calculateRelevance($memory, $query),
                ];
            }
        }

        // Sort by relevance and importance
        usort($memories, function ($a, $b) {
            $scoreA = ($a['relevance'] * 0.7) + ($a['importance'] * 0.3);
            $scoreB = ($b['relevance'] * 0.7) + ($b['importance'] * 0.3);
            return $scoreB <=> $scoreA;
        });

        return array_slice($memories, 0, $limit);
    }

    /**
     * Update memory importance.
     */
    public function updateImportance(MemoryStore $memory, int $newImportance): void
    {
        $memory->update(['importance_score' => $newImportance]);
    }

    /**
     * Extend memory expiration.
     */
    public function extendMemory(MemoryStore $memory, ?Carbon $newExpiration = null): void
    {
        $memory->extend($newExpiration);
    }

    /**
     * Delete a memory.
     */
    public function delete(MemoryStore $memory): void
    {
        $memory->delete();
    }

    /**
     * Clean up expired memories.
     */
    public function cleanupExpired(): int
    {
        $deleted = MemoryStore::cleanupExpired();
        
        Log::info('Expired memories cleaned up', ['count' => $deleted]);
        
        return $deleted;
    }

    /**
     * Clean up memories by importance for a user.
     */
    public function cleanupByImportance(User $user, int $limit = 1000): int
    {
        $deleted = MemoryStore::cleanupByImportance($user->id, $limit);
        
        Log::info('Memories cleaned up by importance', [
            'user_id' => $user->id,
            'count' => $deleted,
        ]);
        
        return $deleted;
    }

    /**
     * Get memory statistics for a user.
     */
    public function getStatistics(User $user): array
    {
        $stats = MemoryStore::where('user_id', $user->id)
            ->selectRaw('
                memory_type,
                COUNT(*) as count,
                AVG(importance_score) as avg_importance,
                MAX(importance_score) as max_importance,
                SUM(access_count) as total_accesses
            ')
            ->groupBy('memory_type')
            ->get()
            ->keyBy('memory_type');

        $totalMemories = MemoryStore::where('user_id', $user->id)->count();
        $expiredMemories = MemoryStore::where('user_id', $user->id)->expired()->count();

        return [
            'total_memories' => $totalMemories,
            'expired_memories' => $expiredMemories,
            'active_memories' => $totalMemories - $expiredMemories,
            'by_type' => [
                'short_term' => [
                    'count' => $stats->get(MemoryStore::TYPE_SHORT_TERM)?->count ?? 0,
                    'avg_importance' => $stats->get(MemoryStore::TYPE_SHORT_TERM)?->avg_importance ?? 0,
                    'total_accesses' => $stats->get(MemoryStore::TYPE_SHORT_TERM)?->total_accesses ?? 0,
                ],
                'long_term' => [
                    'count' => $stats->get(MemoryStore::TYPE_LONG_TERM)?->count ?? 0,
                    'avg_importance' => $stats->get(MemoryStore::TYPE_LONG_TERM)?->avg_importance ?? 0,
                    'total_accesses' => $stats->get(MemoryStore::TYPE_LONG_TERM)?->total_accesses ?? 0,
                ],
                'working' => [
                    'count' => $stats->get(MemoryStore::TYPE_WORKING)?->count ?? 0,
                    'avg_importance' => $stats->get(MemoryStore::TYPE_WORKING)?->avg_importance ?? 0,
                    'total_accesses' => $stats->get(MemoryStore::TYPE_WORKING)?->total_accesses ?? 0,
                ],
                'meta' => [
                    'count' => $stats->get(MemoryStore::TYPE_META)?->count ?? 0,
                    'avg_importance' => $stats->get(MemoryStore::TYPE_META)?->avg_importance ?? 0,
                    'total_accesses' => $stats->get(MemoryStore::TYPE_META)?->total_accesses ?? 0,
                ],
            ],
        ];
    }

    /**
     * Consolidate memories (move important short-term to long-term).
     */
    public function consolidateMemories(User $user): int
    {
        $consolidated = 0;
        $threshold = 7; // Importance threshold for consolidation

        $shortTermMemories = MemoryStore::where('user_id', $user->id)
            ->ofType(MemoryStore::TYPE_SHORT_TERM)
            ->where('importance_score', '>=', $threshold)
            ->where('access_count', '>=', 2) // Accessed at least twice
            ->get();

        foreach ($shortTermMemories as $memory) {
            // Create long-term memory
            $this->store(
                $user,
                MemoryStore::TYPE_LONG_TERM,
                $memory->key,
                $memory->getDecodedValue(),
                $memory->context,
                [
                    'metadata' => $memory->metadata,
                    'importance' => $memory->importance_score,
                ]
            );

            // Delete short-term memory
            $memory->delete();
            $consolidated++;
        }

        Log::info('Memories consolidated', [
            'user_id' => $user->id,
            'count' => $consolidated,
        ]);

        return $consolidated;
    }

    /**
     * Check if a memory is relevant to a query.
     */
    protected function isRelevant(MemoryStore $memory, string $query): bool
    {
        $value = is_string($memory->value) ? $memory->value : json_encode($memory->value);
        $key = $memory->key;

        // Simple relevance check - in production, use more sophisticated methods
        $queryWords = explode(' ', strtolower($query));
        $memoryText = strtolower($key . ' ' . $value);

        foreach ($queryWords as $word) {
            if (strlen($word) > 3 && strpos($memoryText, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate relevance score between memory and query.
     */
    protected function calculateRelevance(MemoryStore $memory, string $query): float
    {
        $value = is_string($memory->value) ? $memory->value : json_encode($memory->value);
        $memoryText = strtolower($memory->key . ' ' . $value);
        $queryText = strtolower($query);

        // Simple word overlap scoring
        $queryWords = array_unique(explode(' ', $queryText));
        $memoryWords = array_unique(explode(' ', $memoryText));

        $intersection = array_intersect($queryWords, $memoryWords);
        $union = array_unique(array_merge($queryWords, $memoryWords));

        if (empty($union)) {
            return 0;
        }

        return count($intersection) / count($union);
    }
}
