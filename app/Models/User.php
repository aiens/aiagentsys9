<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'preferences',
        'ai_settings',
        'is_active',
        'last_login_at',
        'timezone',
        'language',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'preferences' => 'array',
        'ai_settings' => 'array',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user's conversations.
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the user's knowledge bases.
     */
    public function knowledgeBases()
    {
        return $this->hasMany(KnowledgeBase::class);
    }

    /**
     * Get the user's workflows.
     */
    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }

    /**
     * Get the user's memory stores.
     */
    public function memoryStores()
    {
        return $this->hasMany(MemoryStore::class);
    }

    /**
     * Get the user's AI model usage.
     */
    public function aiModelUsage()
    {
        return $this->hasMany(AiModelUsage::class);
    }

    /**
     * Get the user's generation tasks.
     */
    public function generationTasks()
    {
        return $this->hasMany(GenerationTask::class);
    }

    /**
     * Get the user's MCP tool executions.
     */
    public function mcpToolExecutions()
    {
        return $this->hasMany(McpToolExecution::class);
    }

    /**
     * Get the user's default AI settings.
     */
    public function getDefaultAiSettings(): array
    {
        return $this->ai_settings ?? [
            'default_model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'streaming' => true,
        ];
    }

    /**
     * Update the user's last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the user's preferred timezone.
     */
    public function getTimezone(): string
    {
        return $this->timezone ?? config('app.timezone');
    }

    /**
     * Get the user's preferred language.
     */
    public function getLanguage(): string
    {
        return $this->language ?? config('app.locale');
    }
}
