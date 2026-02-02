# Archived Legacy Migration Commands

This directory contains legacy migration commands that are no longer functional because the legacy tables/models have been removed.

**DO NOT USE THESE COMMANDS** - They are archived for reference only.

## Archived Commands

- `VerifyLegacyTablesEmpty.php` - Verified legacy tables were empty before dropping (no longer needed)
- `MigrateCustomersToParties.php` - Migrated customers to parties (customers table dropped)
- `MigrateCustomerTransactions.php` - Migrated customer_transactions to documents/payments (table dropped)
- `VerifyMigration.php` - Verified migration integrity (legacy models removed)

## Migration Date

Archived: 2026-02-02

## Reason

These commands referenced legacy models (`Customer`, `CustomerTransaction`, `FinanceTransaction`, etc.) that have been deleted as part of the legacy accounting system removal.

The tables they migrated have been dropped via migration `2026_02_02_100000_drop_legacy_accounting_tables.php`.

## Current Migration Command

The main migration command `MigrateToNewAccounting.php` still exists but has been updated:
- Legacy `customers` and `finance` steps have been disabled
- Only `parties`, `cashboxes`, `categories`, `payroll`, and `overtimes` steps remain active
