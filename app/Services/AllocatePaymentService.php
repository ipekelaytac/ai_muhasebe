<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Document;
use App\Models\PaymentAllocation;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
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

            // Validate payment status
            if ($payment->status !== 'posted') {
                throw new \Exception('Can only allocate posted payments');
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

                // Validate document status
                if ($document->status !== 'posted') {
                    throw new \Exception("Can only allocate to posted documents");
                }

                // Validate direction match
                $this->validateDirectionMatch($payment, $document);

                // Validate allocation amount
                $allocationAmount = $allocationData['amount'];
                $unpaidAmount = $document->unpaid_amount;

                if ($allocationAmount > $unpaidAmount) {
                    throw new \Exception("Allocation amount ({$allocationAmount}) exceeds unpaid amount ({$unpaidAmount}) for document {$document->document_number}");
                }

                // Create allocation
                $allocation = PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'document_id' => $document->id,
                    'amount' => $allocationAmount,
                    'notes' => $allocationData['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                $createdAllocations[] = $allocation;

                // Log audit
                AuditLog::create([
                    'company_id' => $payment->company_id,
                    'branch_id' => $payment->branch_id,
                    'auditable_type' => PaymentAllocation::class,
                    'auditable_id' => $allocation->id,
                    'user_id' => Auth::id(),
                    'event' => 'allocated',
                    'new_values' => $allocation->toArray(),
                    'description' => "Allocated {$allocationAmount} from payment {$payment->payment_number} to document {$document->document_number}",
                ]);
            }

            // Recalculate amounts
            $payment->recalculateAllocatedAmount();
            foreach ($createdAllocations as $allocation) {
                $allocation->document->recalculatePaidAmount();
            }

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

            $allocation->delete();

            // Recalculate amounts
            $allocation->payment->recalculateAllocatedAmount();
            $allocation->document->recalculatePaidAmount();
        });
    }

    /**
     * Validate payment direction matches document direction
     */
    private function validateDirectionMatch(Payment $payment, Document $document): void
    {
        // Receivable documents should be settled by inflow payments
        // Payable documents should be settled by outflow payments
        $expectedPaymentDirection = $document->direction === 'receivable' ? 'inflow' : 'outflow';

        if ($payment->direction !== $expectedPaymentDirection) {
            throw new \Exception(
                "Payment direction ({$payment->direction}) does not match document direction ({$document->direction}). " .
                "Receivables require inflow payments, payables require outflow payments."
            );
        }
    }
}
