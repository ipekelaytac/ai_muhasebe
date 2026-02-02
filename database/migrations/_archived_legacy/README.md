# Archived Legacy Migrations

This directory contains legacy accounting migrations that have been replaced by the new accounting system.

**DO NOT RUN THESE MIGRATIONS** - They are archived for reference only.

## Archived Migrations

- `2025_12_31_133347_create_customers_table.php` → Replaced by `parties` table
- `2025_12_31_133351_create_customer_transactions_table.php` → Replaced by `documents` + `payments` + `payment_allocations`
- `2025_12_27_130423_create_finance_transactions_table.php` → Replaced by `documents`
- `2025_12_31_133930_create_checks_table.php` → Replaced by `cheques` table
- `2025_12_27_130415_create_advances_table.php` → Replaced by `documents` (type: `advance_given`/`advance_received`)
- `2025_12_27_130417_create_advance_settlements_table.php` → Replaced by `payment_allocations`
- `2025_12_27_130425_create_transaction_attachments_table.php` → Replaced by `document_attachments`

## Migration Date

Archived: 2026-02-02

## Reason

These tables were part of the legacy accounting system and have been completely replaced by the new accounting system in `app/Domain/Accounting/`.

The tables were dropped via migration `2026_02_02_100000_drop_legacy_accounting_tables.php`.
