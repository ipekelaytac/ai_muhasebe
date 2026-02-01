<?php

namespace App\Domain\Accounting\Enums;

class ChequeStatus
{
    public const IN_PORTFOLIO = 'in_portfolio';
    public const ENDORSED = 'endorsed';
    public const DEPOSITED = 'deposited';
    public const COLLECTED = 'collected';
    public const BOUNCED = 'bounced';
    public const CANCELLED = 'cancelled';
    public const PAID = 'paid';
    public const PENDING_ISSUE = 'pending_issue';
    
    public const ALL = [
        self::IN_PORTFOLIO,
        self::ENDORSED,
        self::DEPOSITED,
        self::COLLECTED,
        self::BOUNCED,
        self::CANCELLED,
        self::PAID,
        self::PENDING_ISSUE,
    ];
    
    // Statuses that affect cashflow forecast (cheque not yet settled)
    public const FORECAST_STATUSES = [
        self::IN_PORTFOLIO,
        self::DEPOSITED,
        self::PENDING_ISSUE,
    ];
    
    public static function getLabel(string $status): string
    {
        return match ($status) {
            self::IN_PORTFOLIO => 'Portföyde',
            self::ENDORSED => 'Ciro Edildi',
            self::DEPOSITED => 'Bankaya Verildi',
            self::COLLECTED => 'Tahsil Edildi',
            self::BOUNCED => 'Karşılıksız',
            self::CANCELLED => 'İptal',
            self::PAID => 'Ödendi',
            self::PENDING_ISSUE => 'Verilecek',
            default => $status,
        };
    }
}
