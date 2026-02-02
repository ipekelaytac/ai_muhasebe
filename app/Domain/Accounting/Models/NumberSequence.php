<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NumberSequence extends Model
{
    protected $table = 'number_sequences';
    
    protected $fillable = [
        'company_id',
        'branch_id',
        'type',
        'subtype',
        'year',
        'prefix',
        'last_number',
        'suffix',
    ];
    
    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
    ];
    
    /**
     * Get next number in sequence (thread-safe using database-level atomic increment)
     */
    public static function getNext(
        int $companyId,
        ?int $branchId,
        string $type,
        ?string $subtype,
        int $year
    ): int {
        return DB::transaction(function () use ($companyId, $branchId, $type, $subtype, $year) {
            // Build query with proper null handling for branch_id and subtype
            $query = static::where('company_id', $companyId)
                ->where('type', $type)
                ->where('year', $year);
            
            if ($branchId === null) {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', $branchId);
            }
            
            if ($subtype === null) {
                $query->whereNull('subtype');
            } else {
                $query->where('subtype', $subtype);
            }
            
            // Lock the row for update to prevent race conditions
            $sequence = $query->lockForUpdate()->first();
            
            if (!$sequence) {
                // Try to create the sequence, handling race condition
                try {
                    $sequence = static::create([
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'type' => $type,
                        'subtype' => $subtype,
                        'year' => $year,
                        'last_number' => 0, // Will be incremented below
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // If duplicate key error, another request created it, reload with lock
                    if ($e->getCode() == 23000) {
                        $sequence = $query->lockForUpdate()->firstOrFail();
                    } else {
                        throw $e;
                    }
                }
            }
            
            // Atomically increment last_number using database-level increment
            $sequence->increment('last_number');
            
            // Return the incremented value
            return (int) $sequence->fresh()->last_number;
        });
    }
    
    /**
     * Get current number (without incrementing)
     */
    public static function getCurrent(
        int $companyId,
        ?int $branchId,
        string $type,
        ?string $subtype,
        int $year
    ): int {
        $sequence = static::where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('type', $type)
            ->where('subtype', $subtype)
            ->where('year', $year)
            ->first();
        
        return $sequence ? $sequence->last_number : 0;
    }
    
    /**
     * Reset sequence to specific number
     */
    public static function reset(
        int $companyId,
        ?int $branchId,
        string $type,
        ?string $subtype,
        int $year,
        int $number = 0
    ): void {
        static::updateOrCreate(
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'type' => $type,
                'subtype' => $subtype,
                'year' => $year,
            ],
            [
                'last_number' => $number,
            ]
        );
    }
}
