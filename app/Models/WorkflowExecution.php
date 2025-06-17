<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowExecution extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workflow_id',
        'user_id',
        'execution_id',
        'status',
        'input_data',
        'output_data',
        'variables',
        'context',
        'error_message',
        'total_nodes',
        'completed_nodes',
        'failed_nodes',
        'started_at',
        'completed_at',
        'execution_time_ms',
        'total_cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'variables' => 'array',
        'context' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_cost' => 'decimal:6',
    ];

    /**
     * Status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the workflow that owns this execution.
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get the user that owns this execution.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the execution logs for this execution.
     */
    public function logs()
    {
        return $this->hasMany(WorkflowExecutionLog::class);
    }

    /**
     * Scope a query to only include executions with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include running executions.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope a query to only include completed executions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include failed executions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Start the execution.
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Complete the execution successfully.
     */
    public function complete(array $outputData = []): void
    {
        $completedAt = now();
        $executionTime = $this->started_at ? 
            $this->started_at->diffInMilliseconds($completedAt) : null;

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'output_data' => $outputData,
            'completed_at' => $completedAt,
            'execution_time_ms' => $executionTime,
        ]);
    }

    /**
     * Mark the execution as failed.
     */
    public function fail(string $errorMessage): void
    {
        $completedAt = now();
        $executionTime = $this->started_at ? 
            $this->started_at->diffInMilliseconds($completedAt) : null;

        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => $completedAt,
            'execution_time_ms' => $executionTime,
        ]);
    }

    /**
     * Cancel the execution.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Update the progress of the execution.
     */
    public function updateProgress(int $completedNodes, int $failedNodes = 0): void
    {
        $this->update([
            'completed_nodes' => $completedNodes,
            'failed_nodes' => $failedNodes,
        ]);
    }

    /**
     * Add cost to the execution.
     */
    public function addCost(float $cost): void
    {
        $this->increment('total_cost', $cost);
    }

    /**
     * Check if the execution is running.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if the execution is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the execution has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the execution was cancelled.
     */
    public function wasCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_nodes === 0) {
            return 0;
        }

        return ($this->completed_nodes / $this->total_nodes) * 100;
    }

    /**
     * Get the execution duration in human readable format.
     */
    public function getDurationForHumans(): ?string
    {
        if (!$this->execution_time_ms) {
            return null;
        }

        $seconds = $this->execution_time_ms / 1000;

        if ($seconds < 60) {
            return round($seconds, 1) . 's';
        }

        $minutes = $seconds / 60;
        if ($minutes < 60) {
            return round($minutes, 1) . 'm';
        }

        $hours = $minutes / 60;
        return round($hours, 1) . 'h';
    }
}
