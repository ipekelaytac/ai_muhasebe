<?php

namespace App\Domain\Accounting\Enums;

/**
 * Payment Types
 */
class PaymentType
{
    public const CASH_IN = 'cash_in';
    public const CASH_OUT = 'cash_out';
    public const BANK_IN = 'bank_in';
    public const BANK_OUT = 'bank_out';
    public const BANK_TRANSFER = 'bank_transfer';
    public const POS_IN = 'pos_in';
    public const CHEQUE_IN = 'cheque_in';
    public const CHEQUE_OUT = 'cheque_out';
    public const TRANSFER = 'transfer';
    
    public const ALL = [
        self::CASH_IN,
        self::CASH_OUT,
        self::BANK_IN,
        self::BANK_OUT,
        self::BANK_TRANSFER,
        self::POS_IN,
        self::CHEQUE_IN,
        self::CHEQUE_OUT,
        self::TRANSFER,
    ];
    
    public const IN_TYPES = [
        self::CASH_IN,
        self::BANK_IN,
        self::POS_IN,
        self::CHEQUE_IN,
    ];
    
    public const OUT_TYPES = [
        self::CASH_OUT,
        self::BANK_OUT,
        self::CHEQUE_OUT,
    ];
    
    public const CASH_TYPES = [
        self::CASH_IN,
        self::CASH_OUT,
    ];
    
    public const BANK_TYPES = [
        self::BANK_IN,
        self::BANK_OUT,
        self::BANK_TRANSFER,
        self::POS_IN,
    ];
    
    /**
     * Get default direction for payment type
     */
    public static function getDirection(string $type): string
    {
        return in_array($type, self::IN_TYPES) ? 'in' : 'out';
    }
    
    /**
     * Get Turkish label for payment type
     */
    public static function getLabel(string $type): string
    {
        return match ($type) {
            self::CASH_IN => 'Kasa Girişi',
            self::CASH_OUT => 'Kasa Çıkışı',
            self::BANK_IN => 'Banka Girişi',
            self::BANK_OUT => 'Banka Çıkışı',
            self::BANK_TRANSFER => 'Havale/EFT',
            self::POS_IN => 'POS Tahsilat',
            self::CHEQUE_IN => 'Çek Tahsilat',
            self::CHEQUE_OUT => 'Çek Ödeme',
            self::TRANSFER => 'Virman',
            default => $type,
        };
    }
}
