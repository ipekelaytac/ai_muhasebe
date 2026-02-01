# Accounting System Architecture

## High-Level Overview

This system implements a **double-entry accounting model** centered on three core concepts:

1. **Documents (Obligations/Accruals)**: Financial obligations that create receivables or payables
2. **Payments (Cash Movements)**: Actual cash/bank movements
3. **Allocations (Settlements)**: Links payments to obligations, enabling partial payments and splitting

## Core Principles

- **Never compute balances from stored fields** - balances derive from obligations minus allocations
- **Support partial payments** - one payment can settle multiple obligations, one obligation can be settled by multiple payments
- **Auditability** - all records track who created/updated and when; no hard deletes
- **Period locking** - closed periods cannot be edited; changes go through reversals
- **Company/Branch scoping** - all financial records are scoped to company + branch

## Data Flow

```
┌─────────────────┐
│   Document      │  (Obligation created: supplier_invoice, customer_invoice, payroll_due, etc.)
│   (Accrual)     │  → Creates receivable/payable
└────────┬────────┘
         │
         │ (due_date, amount, direction)
         │
         ▼
┌─────────────────┐
│   Payment       │  (Cash movement: cash_in, bank_out, transfer, etc.)
│   (Cash Flow)   │  → Affects cashbox/bank balance
└────────┬────────┘
         │
         │ (amount, direction, date)
         │
         ▼
┌─────────────────┐
│  Allocation     │  (Settlement: links payment to document)
│  (Settlement)   │  → Reduces unpaid amount of document
└─────────────────┘
```

## Modules

### 1. Party Management
- **Parties**: Unified model for customers, suppliers, employees, others
- Uses polymorphic relationship OR separate tables with unified interface
- Tracks balances via documents and allocations (not stored balance field)

### 2. Document Management
- **Documents**: All obligations (invoices, dues, cheques, adjustments)
- **Document Lines**: Optional detail lines for categorization, tax splits
- Document types: supplier_invoice, customer_invoice, expense_due, payroll_due, overtime_due, meal_due, cheque_receivable, cheque_payable, adjustment
- Status: draft, posted, reversed, canceled
- Direction: receivable (we expect to receive) or payable (we expect to pay)

### 3. Payment Management
- **Payments**: All cash/bank movements
- Payment types: cash_in, cash_out, bank_in, bank_out, transfer, pos_in
- Direction: inflow or outflow
- Links to cashbox or bank_account

### 4. Allocation Management
- **Allocations**: Links payments to documents
- Supports partial allocations
- Validation: allocation amount ≤ unpaid document amount
- Validation: sum(allocations) ≤ payment amount (unless advance)

### 5. Cash & Bank Management
- **Cashboxes**: Physical cash locations
- **Bank Accounts**: Bank accounts
- Balances computed from payments (not stored)

### 6. Cheque Management
- **Cheques**: Received or issued cheques
- Status tracking: received/issued, in_portfolio, endorsed, bank_submitted, paid, bounced, canceled
- Affects cashflow forecast based on due_date

### 7. Period Management
- **Periods**: Accounting periods (months)
- Lock status: open, locked
- Locked periods: no edits allowed, only reversals in open period

### 8. Reporting
- Cash/Bank balances (real-time from payments)
- Aging reports (payables/receivables by due_date buckets)
- Cashflow forecast (expected inflows/outflows)
- Party statements (cari ekstre)
- P&L reports (income/expense by category)

## Database Schema Overview

### Core Tables
- `parties` - Customers, suppliers, employees, others
- `documents` - All obligations/accruals
- `document_lines` - Optional detail lines
- `payments` - Cash/bank movements
- `payment_allocations` - Settlements
- `cashboxes` - Cash locations
- `bank_accounts` - Bank accounts
- `cheques` - Cheque tracking
- `accounting_periods` - Period locking
- `audit_logs` - Change tracking

### Supporting Tables
- `finance_categories` - Expense/income categories (existing)
- `attachments` - Polymorphic attachments (existing)

## Service Layer

### CreateObligationService
- Validates document data
- Creates document + document_lines
- Sets status to posted
- Enforces period lock check

### RecordPaymentService
- Validates payment data
- Validates cashbox/bank balance (for outflows)
- Creates payment record
- Updates cashbox/bank balance (computed, not stored)

### AllocatePaymentService
- Validates allocation rules
- Creates allocation records
- Handles partial payments
- Handles overpayments (creates advance credit document)

### ReverseDocumentService
- Creates reversal document
- Links to original document
- Validates period lock

### LockPeriodService
- Locks accounting period
- Prevents edits in locked period
- Allows only reversals

## Security & Permissions

- Use Spatie Permission package
- Roles: admin, accountant, viewer
- Permissions:
  - documents.create, documents.update, documents.delete
  - payments.create, payments.update, payments.delete
  - allocations.create, allocations.delete
  - periods.lock, periods.unlock
  - reports.view

## Migration Strategy

1. Create new tables alongside existing ones
2. Map existing data:
   - `customer_transactions` → `documents` + `payments` + `allocations`
   - `finance_transactions` → `documents` + `payments` + `allocations`
   - `payroll_items` → `documents` (payroll_due)
   - `checks` → `documents` (cheque_receivable/payable)
3. Run migration scripts to transform data
4. Gradually migrate UI to use new endpoints
5. Deprecate old endpoints after full migration
