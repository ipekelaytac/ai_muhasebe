<?php

namespace App\Domain\Accounting\Enums;

class PartyType
{
    public const CUSTOMER = 'customer';
    public const SUPPLIER = 'supplier';
    public const EMPLOYEE = 'employee';
    public const OTHER = 'other';
    public const TAX_AUTHORITY = 'tax_authority';
    public const BANK = 'bank';
    
    public const ALL = [
        self::CUSTOMER,
        self::SUPPLIER,
        self::EMPLOYEE,
        self::OTHER,
        self::TAX_AUTHORITY,
        self::BANK,
    ];
    
    public static function getLabel(string $type): string
    {
        return match ($type) {
            self::CUSTOMER => 'Müşteri',
            self::SUPPLIER => 'Tedarikçi',
            self::EMPLOYEE => 'Çalışan',
            self::OTHER => 'Diğer',
            self::TAX_AUTHORITY => 'Vergi Dairesi',
            self::BANK => 'Banka',
            default => $type,
        };
    }
}
