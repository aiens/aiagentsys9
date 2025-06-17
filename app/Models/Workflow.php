<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Workflow extends Model
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
        'definition',
        'version',
        'status',
        'settings',
        'variables',
        'is_public',
        'is_template',
        'category',
        'tags',
        'execution_count',
        'last_executed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'definition' => 'array',
        'settings' => 'array',
        'variables' => 'array',
        'tags' => 'array',
        'is_public' => 'boolean',
        'is_template' => 'boolean',
        'last_executed_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'version', 'is_public'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user that owns this workflow.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the executions for this workflow.
     */
    public function executions()
    {
        return $this->hasMany(WorkflowExecution::class);
    }

    /**
     * Get the latest execution for this workflow.
     */
    public function latestExecution()
    {
        return $this->hasOne(WorkflowExecution::class)->latestOfMany();
    }

    /**
     * Scope a query to only include workflows with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include active workflows.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include public workflows.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include template workflows.
     */
    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to filter by tags.
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Execute this workflow with the given input data.
     */
    public function execute(array $inputData = [], ?array $variables = null): WorkflowExecution
    {
        $execution = $this->executions()->create([
            'user_id' => $this->user_id,
            'execution_id' => $this->generateExecutionId(),
            'input_data' => $inputData,
            'variables' => array_merge($this->variables ?? [], $variables ?? []),
            'status' => WorkflowExecution::STATUS_PENDING,
            'total_nodes' => $this->countNodes(),
        ]);

        $this->increment('execution_count');
        $this->update(['last_executed_at' => now()]);

        return $execution;
    }

    /**
     * Create a copy of this workflow.
     */
    public function duplicate(string $newName = null): self
    {
        $copy = $this->replicate();
        $copy->name = $newName ?? $this->name . ' (Copy)';
        $copy->status = self::STATUS_DRAFT;
        $copy->execution_count = 0;
        $copy->last_executed_at = null;
        $copy->save();

        return $copy;
    }

    /**
     * Publish this workflow as a template.
     */
    public function publishAsTemplate(): void
    {
        $this->update([
            'is_template' => true,
            'is_public' => true,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Validate the workflow definition.
     */
    public function validateDefinition(): array
    {
        $errors = [];
        $definition = $this->definition;

        if (!isset($definition['nodes']) || !is_array($definition['nodes'])) {
            $errors[] = 'Workflow must have nodes array';
        }

        if (!isset($definition['edges']) || !is_array($definition['edges'])) {
            $errors[] = 'Workflow must have edges array';
        }

        // Validate nodes
        foreach ($definition['nodes'] ?? [] as $node) {
            if (!isset($node['id']) || !isset($node['type'])) {
                $errors[] = 'Each node must have id and type';
            }

            $nodeTypes = array_keys(config('workflow.node_types', []));
            if (isset($node['type']) && !in_array($node['type'], $nodeTypes)) {
                $errors[] = "Unknown node type: {$node['type']}";
            }
        }

        // Validate edges
        $nodeIds = collect($definition['nodes'] ?? [])->pluck('id')->toArray();
        foreach ($definition['edges'] ?? [] as $edge) {
            if (!isset($edge['source']) || !isset($edge['target'])) {
                $errors[] = 'Each edge must have source and target';
            }

            if (isset($edge['source']) && !in_array($edge['source'], $nodeIds)) {
                $errors[] = "Edge source node not found: {$edge['source']}";
            }

            if (isset($edge['target']) && !in_array($edge['target'], $nodeIds)) {
                $errors[] = "Edge target node not found: {$edge['target']}";
            }
        }

        return $errors;
    }

    /**
     * Count the number of nodes in this workflow.
     */
    public function countNodes(): int
    {
        return count($this->definition['nodes'] ?? []);
    }

    /**
     * Get the workflow settings with defaults.
     */
    public function getSettingsWithDefaults(): array
    {
        $defaults = [
            'max_execution_time' => config('workflow.engine.max_execution_time', 3600),
            'max_parallel_tasks' => config('workflow.engine.max_parallel_tasks', 10),
            'error_strategy' => config('workflow.error_handling.default_strategy', 'stop'),
            'retry_attempts' => config('workflow.engine.max_retry_attempts', 3),
        ];

        return array_merge($defaults, $this->settings ?? []);
    }

    /**
     * Check if the workflow is accessible by a user.
     */
    public function isAccessibleBy(User $user): bool
    {
        return $this->user_id === $user->id || 
               ($this->is_public && $this->status === self::STATUS_ACTIVE) ||
               $user->hasPermissionTo('access_all_workflows');
    }

    /**
     * Get the success rate of this workflow.
     */
    public function getSuccessRate(): float
    {
        if ($this->execution_count === 0) {
            return 0;
        }

        $successfulExecutions = $this->executions()
            ->where('status', WorkflowExecution::STATUS_COMPLETED)
            ->count();

        return ($successfulExecutions / $this->execution_count) * 100;
    }

    /**
     * Get the average execution time.
     */
    public function getAverageExecutionTime(): ?int
    {
        return $this->executions()
            ->whereNotNull('execution_time_ms')
            ->avg('execution_time_ms');
    }

    /**
     * Generate a unique execution ID.
     */
    protected function generateExecutionId(): string
    {
        return 'wf_' . $this->id . '_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    }
}
