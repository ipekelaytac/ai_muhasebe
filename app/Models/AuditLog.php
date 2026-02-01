<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    // Schema only has created_at, not updated_at
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null; // Schema does NOT have updated_at

    protected $fillable = [
        'company_id',
        // Schema does NOT have branch_id column
        'auditable_type',
        'auditable_id',
        'action', // Schema uses 'action' enum, not 'event'
        'old_values',
        'new_values',
        'user_id',
        'user_name', // Schema has user_name
        'ip_address',
        'user_agent',
        // Schema does NOT have description column
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForAuditable($query, $type, $id)
    {
        return $query->where('auditable_type', $type)
            ->where('auditable_id', $id);
    }
}
