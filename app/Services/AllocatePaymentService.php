<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Document;
use App\Models\PaymentAllocation;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class AllocatePaymentService
{
    /**
     * Allocate payment to one or more documents
     *
     * @param Payment $payment
     * @param array $allocations Array of ['document_id' => X, 'amount' => Y]
     * @return array Created allocations
     * @throws \Exception
     */
    public function allocate(Payment $payment, array $allocations): array
    {
        return DB::transaction(function () use ($payment, $allocations) {
            // Validate payment is not locked
            if ($payment->isLocked()) {
                throw new \Exception('Cannot allocate payment in locked period');
            }

            // Validate payment status (schema uses 'confirmed', not 'posted')
            if ($payment->status !== 'confirmed') {
                throw new \Exception('Can only allocate confirmed payments');
            }

            $totalAllocationAmount = array_sum(array_column($allocations, 'amount'));
            $currentUnallocated = $payment->unallocated_amount;

            // Check if total allocation exceeds unallocated amount
            if ($totalAllocationAmount > $currentUnallocated) {
                throw new \Exception("Total allocation amount ({$totalAllocationAmount}) exceeds unallocated amount ({$currentUnallocated})");
            }

            $createdAllocations = [];

            foreach ($allocations as $allocationData) {
                $document = Document::findOrFail($allocationData['document_id']);

                // Validate document is not locked
                if ($document->isLocked()) {
                    throw new \Exception("Document {$document->document_number} is in locked period");
                }

                // Validate document status (schema uses 'pending'/'partial'/'settled', not 'posted')
                if (!in_array($document->status, ['pending', 'partial'])) {
                    throw new \Exception("Can only allocate to pending or partial documents");
                }

                // Validate direction match
                $this->validateDirectionMatch($payment, $document);

                // Validate allocation amount
                $allocationAmount = $allocationData['amount'];
                $unpaidAmount = $document->unpaid_amount;

                if ($allocationAmount > $unpaidAmount) {
                    throw new \Exception("Allocation amount ({$allocationAmount}) exceeds unpaid amount ({$unpaidAmount}) for document {$document->document_number}");
                }

                // Create allocation (schema requires allocation_date - no default)
                $allocation = PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'document_id' => $document->id,
                    'amount' => $allocationAmount,
                    'allocation_date' => $allocationData['allocation_date'] ?? $payment->payment_date, // Default to payment date
                    'status' => 'active', // Schema default is 'active'
                    'notes' => $allocationData['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                $createdAllocations[] = $allocation;

                // Log audit (schema uses 'action' not 'event', no branch_id/description, only created_at not updated_at)
                $auditData = [
                    'company_id' => $payment->company_id,
                    'auditable_type' => PaymentAllocation::class,
                    'auditable_id' => $allocation->id,
                    'action' => 'create', // Schema uses 'action' enum, not 'event'
                    'new_values' => $allocation->toArray(),
                    'user_id' => Auth::id(),
                    'created_at' => now(), // Schema only has created_at, not updated_at
                ];
                // Filter to only existing columns (schema does NOT have branch_id/description/event/updated_at)
                $auditData = $this->filterByExistingColumns('audit_logs', $auditData);
                AuditLog::create($auditData);
            }

            // Amounts are calculated via accessors - no need to recalculate
            // Payment::getAllocatedAmountAttribute() and Document::getPaidAmountAttribute() calculate on-demand

            // Handle overpayment (if payment is fully allocated but documents still have unpaid amounts)
            // This creates an advance credit document automatically
            if ($payment->unallocated_amount == 0 && $totalAllocationAmount < $payment->amount) {
                // This shouldn't happen due to validation, but handle edge case
            }

            return $createdAllocations;
        });
    }

    /**
     * Remove allocation
     *
     * @param PaymentAllocation $allocation
     * @return void
     * @throws \Exception
     */
    public function removeAllocation(PaymentAllocation $allocation): void
    {
        DB::transaction(function () use ($allocation) {
            if ($allocation->payment->isLocked()) {
                throw new \Exception('Cannot remove allocation in locked period');
            }

            // Cancel allocation by setting status to 'cancelled' (schema doesn't have soft deletes)
            $allocation->status = 'cancelled';
            $allocation->save();

            // Amounts are calculated via accessors - no need to recalculate
            // Payment::getAllocatedAmountAttribute() and Document::getPaidAmountAttribute() calculate on-demand
        });
    }

    /**
     * Validate payment direction matches document direction
     */
    private function validateDirectionMatch(Payment $payment, Document $document): void
    {
        // Receivable documents should be settled by 'in' payments
        // Payable documents should be settled by 'out' payments
        // Schema uses 'in'/'out', not 'inflow'/'outflow'
        $paymentDirection = $payment->direction; // DB stores 'in'/'out'
        $expectedPaymentDirection = $document->direction === 'receivable' ? 'in' : 'out';

        if ($paymentDirection !== $expectedPaymentDirection) {
            throw new \Exception(
                "Payment direction ({$paymentDirection}) does not match document direction ({$document->direction}). " .
                "Receivables require 'in' payments, payables require 'out' payments."
            );
        }
    }

    /**
     * Filter array to only include columns that exist in the table schema
     *
     * @param string $table
     * @param array $data
     * @return array
     */
    private function filterByExistingColumns(string $table, array $data): array
    {
        $filtered = [];
        foreach ($data as $key => $value) {
            if (Schema::hasColumn($table, $key)) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }
}
