# Quick Start Guide

## Requirements
- PHP 8.2 or higher
- Laravel 8.x
- MySQL/MariaDB

## Installation Steps

### 1. Run Migrations
```bash
php artisan migrate:fresh --seed
```

This will:
- Create all database tables (including accounting core tables)
- Seed base data (companies, branches, users, accounting permissions/roles, default cashboxes, periods)

### 2. Migrate Existing Data (Optional)
```bash
# Dry run first to see what will happen
php artisan accounting:migrate-customers-to-parties --dry-run
php artisan accounting:migrate-customers-to-parties

php artisan accounting:migrate-customer-transactions --dry-run
php artisan accounting:migrate-customer-transactions

# Verify migration
php artisan accounting:verify-migration
```

## API Endpoints

### Parties
```
GET    /api/accounting/parties
POST   /api/accounting/parties
GET    /api/accounting/parties/{id}
PUT    /api/accounting/parties/{id}
DELETE /api/accounting/parties/{id}
```

### Documents (Obligations)
```
GET    /api/accounting/documents
POST   /api/accounting/documents
GET    /api/accounting/documents/{id}
PUT    /api/accounting/documents/{id}
DELETE /api/accounting/documents/{id}
POST   /api/accounting/documents/{id}/reverse
```

### Payments
```
GET    /api/accounting/payments
POST   /api/accounting/payments
GET    /api/accounting/payments/{id}
PUT    /api/accounting/payments/{id}
DELETE /api/accounting/payments/{id}
```

### Allocations
```
POST   /api/accounting/payments/{payment}/allocations
DELETE /api/accounting/payments/{payment}/allocations/{allocation}
```

### Reports
```
GET /api/accounting/reports/cash-bank-balance?company_id=1&branch_id=1
GET /api/accounting/reports/payables-aging?company_id=1&branch_id=1&as_of_date=2026-02-01
GET /api/accounting/reports/receivables-aging?company_id=1&branch_id=1&as_of_date=2026-02-01
GET /api/accounting/reports/employee-dues-aging?company_id=1&branch_id=1
GET /api/accounting/reports/cashflow-forecast?company_id=1&branch_id=1&days=30
GET /api/accounting/reports/party-statement/{party}?start_date=2026-01-01&end_date=2026-01-31
GET /api/accounting/reports/profit-loss?company_id=1&branch_id=1&start_date=2026-01-01&end_date=2026-01-31
```

## Common Workflows

### 1. Create a Supplier Invoice (Tahakkuk)
```php
POST /api/accounting/documents
{
    "company_id": 1,
    "branch_id": 1,
    "document_type": "supplier_invoice",
    "direction": "payable",
    "party_id": 5,
    "document_date": "2026-01-15",
    "due_date": "2026-02-15",
    "total_amount": 1000.00,
    "description": "Supplier invoice #12345"
}
```

### 2. Record a Payment (Ödeme)
```php
POST /api/accounting/payments
{
    "company_id": 1,
    "branch_id": 1,
    "payment_type": "cash_out",
    "direction": "outflow",
    "cashbox_id": 1,
    "payment_date": "2026-02-01",
    "amount": 500.00,
    "description": "Partial payment to supplier"
}
```

### 3. Allocate Payment to Document (Kapat/Dağıt)
```php
POST /api/accounting/payments/{payment_id}/allocations
{
    "allocations": [
        {
            "document_id": 10,
            "amount": 500.00,
            "notes": "Partial payment"
        }
    ]
}
```

### 4. Lock a Period
```php
POST /api/accounting/periods/{period_id}/lock
{
    "notes": "Month end close"
}
```

## Key Concepts

### Document Types
- `supplier_invoice` - Supplier invoice (payable)
- `customer_invoice` - Customer invoice (receivable)
- `expense_due` - General expense due
- `payroll_due` - Employee salary due
- `overtime_due` - Overtime payment due
- `meal_due` - Meal allowance due
- `cheque_receivable` - Cheque we received
- `cheque_payable` - Cheque we issued
- `adjustment` - Adjustment entry
- `reversal` - Reversal of another document

### Payment Types
- `cash_in` - Cash received
- `cash_out` - Cash paid
- `bank_in` - Bank deposit
- `bank_out` - Bank withdrawal
- `transfer` - Transfer between accounts
- `pos_in` - POS payment received

### Directions
- Documents: `receivable` (we expect to receive) or `payable` (we expect to pay)
- Payments: `inflow` (money coming in) or `outflow` (money going out)

### Statuses
- Documents: `draft`, `posted`, `reversed`, `canceled`
- Payments: `draft`, `posted`, `reversed`, `canceled`
- Periods: `open`, `locked`

## Important Rules

1. **Receivable documents** must be settled by **inflow payments**
2. **Payable documents** must be settled by **outflow payments**
3. **Allocation amount** cannot exceed unpaid document amount
4. **Total allocations** cannot exceed payment amount
5. **Locked periods** cannot be edited (only reversals allowed)
6. **Balances** are always computed from transactions (never trust stored values alone)

## Testing

Run tests:
```bash
php artisan test --filter Accounting
```

## Troubleshooting

### Migration Issues
- Always use `--dry-run` first
- Check logs: `storage/logs/laravel.log`
- Verify data: `php artisan accounting:verify-migration`

### Balance Discrepancies
- Run recalculation: `php artisan tinker` then manually call `$document->recalculatePaidAmount()`
- Check for soft-deleted allocations
- Verify period assignments

### Period Lock Issues
- Check if period is locked: `AccountingPeriod::find($id)->isLocked()`
- Unlock if needed (admin only): `POST /api/accounting/periods/{id}/unlock`
