# Data Migration Strategy

## Overview

This document outlines the strategy for migrating existing data from the old structure to the new accounting model.

## Migration Approach

1. **Create new tables alongside existing ones** - No data loss, allows gradual migration
2. **Map existing records to new model** - Transform old records into documents/payments/allocations
3. **Run migration scripts** - Artisan commands to perform the transformation
4. **Verify data integrity** - Compare balances and totals
5. **Gradually migrate UI** - Update frontend to use new endpoints
6. **Deprecate old endpoints** - After full migration, remove old code

## Mapping Rules

### 1. Customer Transactions → Documents + Payments + Allocations

**Old Structure:**
- `customer_transactions` table with `type` (income/expense) and `amount`

**New Structure:**
- If `type = 'income'`: Create `document` with `document_type = 'customer_invoice'`, `direction = 'receivable'`
- If `type = 'expense'`: Create `document` with `document_type = 'supplier_invoice'`, `direction = 'payable'`
- Create `payment` if transaction represents actual cash movement
- Create `allocation` to link payment to document

**Migration Logic:**
```php
// For each customer_transaction:
// 1. Determine if it's an accrual (document) or payment
// 2. If accrual: create document
// 3. If payment: create payment + allocation if document exists
```

### 2. Finance Transactions → Documents + Payments

**Old Structure:**
- `finance_transactions` table with `type` (income/expense), `category_id`, `amount`

**New Structure:**
- Income → `document` with `document_type = 'expense_due'` (if internal) or `customer_invoice`
- Expense → `document` with `document_type = 'expense_due'` or `supplier_invoice`
- Link to `party` if `employee_id` exists
- Create `payment` if represents cash movement

### 3. Payroll Items → Documents

**Old Structure:**
- `payroll_items` table with `net_payable`
- `payroll_payments` table with payments

**New Structure:**
- Each `payroll_item` → `document` with `document_type = 'payroll_due'`, `direction = 'payable'`
- Each `payroll_payment` → `payment` with `payment_type = 'cash_out'` or `bank_out'`
- Create `allocation` linking payment to payroll document

### 4. Checks → Documents + Cheques

**Old Structure:**
- `checks` table with customer, amount, dates, status

**New Structure:**
- Create `document` with `document_type = 'cheque_receivable'` or `cheque_payable'`
- Create `cheque` record linked to document
- Update cheque status based on check status

### 5. Advances → Documents

**Old Structure:**
- `advances` table with employee, amount

**New Structure:**
- Create `document` with `document_type = 'expense_due'`, `direction = 'payable'`
- Link to employee party

### 6. Overtimes → Documents

**Old Structure:**
- `overtimes` table with employee, amount

**New Structure:**
- Create `document` with `document_type = 'overtime_due'`, `direction = 'payable'`
- Link to employee party

## Migration Commands

### Command 1: Migrate Customers to Parties

```bash
php artisan accounting:migrate-customers-to-parties
```

- Creates `party` records from `customers` table
- Preserves `partyable_type` and `partyable_id` for reference

### Command 2: Migrate Customer Transactions

```bash
php artisan accounting:migrate-customer-transactions
```

- Transforms customer_transactions into documents/payments/allocations

### Command 3: Migrate Finance Transactions

```bash
php artisan accounting:migrate-finance-transactions
```

- Transforms finance_transactions into documents/payments

### Command 4: Migrate Payroll

```bash
php artisan accounting:migrate-payroll
```

- Transforms payroll_items and payroll_payments into documents/payments/allocations

### Command 5: Migrate Checks

```bash
php artisan accounting:migrate-checks
```

- Transforms checks into documents and cheques

### Command 6: Verify Migration

```bash
php artisan accounting:verify-migration
```

- Compares balances between old and new structures
- Reports discrepancies

## Verification Steps

1. **Balance Verification:**
   - Compare customer balances (old: sum of transactions, new: sum of unpaid documents)
   - Compare cash/bank balances
   - Compare payroll totals

2. **Count Verification:**
   - Count of transactions vs documents+payments
   - Count of allocations vs expected

3. **Date Verification:**
   - Ensure dates are preserved correctly
   - Verify period assignments

## Rollback Strategy

- Keep old tables intact during migration
- Migration commands are idempotent (can be run multiple times safely)
- Use transactions to ensure atomicity
- Create backup before migration

## Post-Migration

1. Run verification command
2. Compare reports (old vs new)
3. Update UI to use new endpoints
4. Monitor for issues
5. After stable period, archive old tables
