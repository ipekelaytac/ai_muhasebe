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
     * Get next number in sequence (thread-safe)
     */
    public static function getNext(
        int $companyId,
        ?int $branchId,
        string $type,
        ?string $subtype,
        int $year
    ): int {
        return DB::transaction(function () use ($companyId, $branchId, $type, $subtype, $year) {
            // Lock the row for update
            $sequence = static::lockForUpdate()
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->where('type', $type)
                ->where('subtype', $subtype)
                ->where('year', $year)
                ->first();
            
            if (!$sequence) {
                $sequence = static::create([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'type' => $type,
                    'subtype' => $subtype,
                    'year' => $year,
                    'last_number' => 1,
                ]);
                
                return 1;
            }
            
            $sequence->increment('last_number');
            
            return $sequence->last_number;
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
