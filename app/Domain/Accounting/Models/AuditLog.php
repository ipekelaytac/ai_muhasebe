<?php

namespace App\Domain\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = false;
    
    protected $table = 'audit_logs';
    
    protected $fillable = [
        'company_id',
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
        'created_at',
    ];
    
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];
    
    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            $log->created_at = now();
        });
    }
    
    // Relationships
    
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
    
    // Scopes
    
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
    
    public function scopeForModel(Builder $query, string $type, int $id): Builder
    {
        return $query->where('auditable_type', $type)->where('auditable_id', $id);
    }
    
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
    
    public function scopeOfAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }
    
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
    
    // Computed Attributes
    
    /**
     * Get action label in Turkish
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'create' => 'Oluşturuldu',
            'update' => 'Güncellendi',
            'delete' => 'Silindi',
            'restore' => 'Geri Yüklendi',
            'status_change' => 'Durum Değişti',
            'lock' => 'Kilitlendi',
            'unlock' => 'Kilit Açıldı',
            default => $this->action,
        };
    }
    
    /**
     * Get changed fields
     */
    public function getChangedFieldsAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return array_keys($this->new_values ?? []);
        }
        
        $changed = [];
        foreach ($this->new_values as $key => $value) {
            if (!isset($this->old_values[$key]) || $this->old_values[$key] !== $value) {
                $changed[] = $key;
            }
        }
        
        return $changed;
    }
    
    /**
     * Get model type short name
     */
    public function getModelNameAttribute(): string
    {
        return class_basename($this->auditable_type);
    }
    
    // Static Methods
    
    /**
     * Log an action
     */
    public static function log(
        Model $model,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        $user = auth()->user();
        $request = request();
        
        return static::create([
            'company_id' => $model->company_id ?? null,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
