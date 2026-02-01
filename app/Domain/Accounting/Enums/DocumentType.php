<?php

namespace App\Domain\Accounting\Enums;

/**
 * Document Types
 * 
 * Each type determines the nature of the obligation.
 */
class DocumentType
{
    public const SUPPLIER_INVOICE = 'supplier_invoice';
    public const CUSTOMER_INVOICE = 'customer_invoice';
    public const EXPENSE_DUE = 'expense_due';
    public const INCOME_DUE = 'income_due';
    public const PAYROLL_DUE = 'payroll_due';
    public const OVERTIME_DUE = 'overtime_due';
    public const MEAL_DUE = 'meal_due';
    public const ADVANCE_GIVEN = 'advance_given';
    public const ADVANCE_RECEIVED = 'advance_received';
    public const CHEQUE_RECEIVABLE = 'cheque_receivable';
    public const CHEQUE_PAYABLE = 'cheque_payable';
    public const ADJUSTMENT_DEBIT = 'adjustment_debit';
    public const ADJUSTMENT_CREDIT = 'adjustment_credit';
    public const OPENING_BALANCE = 'opening_balance';
    
    public const ALL = [
        self::SUPPLIER_INVOICE,
        self::CUSTOMER_INVOICE,
        self::EXPENSE_DUE,
        self::INCOME_DUE,
        self::PAYROLL_DUE,
        self::OVERTIME_DUE,
        self::MEAL_DUE,
        self::ADVANCE_GIVEN,
        self::ADVANCE_RECEIVED,
        self::CHEQUE_RECEIVABLE,
        self::CHEQUE_PAYABLE,
        self::ADJUSTMENT_DEBIT,
        self::ADJUSTMENT_CREDIT,
        self::OPENING_BALANCE,
    ];
    
    public const PAYABLE_TYPES = [
        self::SUPPLIER_INVOICE,
        self::EXPENSE_DUE,
        self::PAYROLL_DUE,
        self::OVERTIME_DUE,
        self::MEAL_DUE,
        self::ADVANCE_RECEIVED,
        self::CHEQUE_PAYABLE,
        self::ADJUSTMENT_DEBIT,
    ];
    
    public const RECEIVABLE_TYPES = [
        self::CUSTOMER_INVOICE,
        self::INCOME_DUE,
        self::ADVANCE_GIVEN,
        self::CHEQUE_RECEIVABLE,
        self::ADJUSTMENT_CREDIT,
    ];
    
    public const EMPLOYEE_TYPES = [
        self::PAYROLL_DUE,
        self::OVERTIME_DUE,
        self::MEAL_DUE,
        self::ADVANCE_GIVEN,
    ];
    
    /**
     * Get default direction for document type
     */
    public static function getDirection(string $type): string
    {
        return in_array($type, self::PAYABLE_TYPES) ? 'payable' : 'receivable';
    }
    
    /**
     * Get Turkish label for document type
     */
    public static function getLabel(string $type): string
    {
        return match ($type) {
            self::SUPPLIER_INVOICE => 'Alım Faturası',
            self::CUSTOMER_INVOICE => 'Satış Faturası',
            self::EXPENSE_DUE => 'Gider Tahakkuku',
            self::INCOME_DUE => 'Gelir Tahakkuku',
            self::PAYROLL_DUE => 'Maaş Tahakkuku',
            self::OVERTIME_DUE => 'Mesai Tahakkuku',
            self::MEAL_DUE => 'Yemek Parası Tahakkuku',
            self::ADVANCE_GIVEN => 'Verilen Avans',
            self::ADVANCE_RECEIVED => 'Alınan Avans',
            self::CHEQUE_RECEIVABLE => 'Alınan Çek',
            self::CHEQUE_PAYABLE => 'Verilen Çek',
            self::ADJUSTMENT_DEBIT => 'Borç Düzeltme',
            self::ADJUSTMENT_CREDIT => 'Alacak Düzeltme',
            self::OPENING_BALANCE => 'Açılış Bakiyesi',
            default => $type,
        };
    }
}
