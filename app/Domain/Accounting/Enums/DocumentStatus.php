<?php

namespace App\Domain\Accounting\Enums;

class DocumentStatus
{
    public const DRAFT = 'draft';
    public const PENDING = 'pending';
    public const PARTIAL = 'partial';
    public const SETTLED = 'settled';
    public const CANCELLED = 'cancelled';
    public const REVERSED = 'reversed';
    
    public const ALL = [
        self::DRAFT,
        self::PENDING,
        self::PARTIAL,
        self::SETTLED,
        self::CANCELLED,
        self::REVERSED,
    ];
    
    public const OPEN = [
        self::PENDING,
        self::PARTIAL,
    ];
    
    public const CLOSED = [
        self::SETTLED,
        self::CANCELLED,
        self::REVERSED,
    ];
    
    public static function getLabel(string $status): string
    {
        return match ($status) {
            self::DRAFT => 'Taslak',
            self::PENDING => 'Bekliyor',
            self::PARTIAL => 'Kısmi Ödendi',
            self::SETTLED => 'Kapandı',
            self::CANCELLED => 'İptal',
            self::REVERSED => 'Ters Kayıt',
            default => $status,
        };
    }
}
