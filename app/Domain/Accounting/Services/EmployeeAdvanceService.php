<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\AuditLog;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;

class EmployeeAdvanceService
{
    protected PeriodService $periodService;
    protected DocumentService $documentService;
    protected PaymentService $paymentService;
    protected AllocationService $allocationService;
    
    public function __construct(
        PeriodService $periodService,
        DocumentService $documentService,
        PaymentService $paymentService,
        AllocationService $allocationService
    ) {
        $this->periodService = $periodService;
        $this->documentService = $documentService;
        $this->paymentService = $paymentService;
        $this->allocationService = $allocationService;
    }
    
    /**
     * Give advance to an employee
     * 
     * Creates:
     * - Payment (cash_out or bank_out) - actual cash movement
     * - Document (advance_given) - employee owes company
     * - Links payment to advance document via reference (but does NOT allocate)
     * 
     * The advance document remains OPEN until deducted in payroll.
     */
    public function giveAdvance(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Validate party is employee
            $party = Party::findOrFail($data['party_id']);
            if ($party->type !== 'employee') {
                throw new \Exception('Sadece personellere avans verilebilir.');
            }
            
            // Validate period is open
            $this->periodService->validatePeriodOpen(
                $data['company_id'],
                $data['advance_date']
            );
            
            // Determine payment type
            $paymentType = $data['payment_source_type'] === 'bank' 
                ? PaymentType::BANK_OUT 
                : PaymentType::CASH_OUT;
            
            // Create advance document (employee owes company - receivable)
            $advanceDocument = $this->documentService->createDocument([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'type' => DocumentType::ADVANCE_GIVEN,
                'direction' => 'receivable', // Employee owes company
                'party_id' => $party->id,
                'document_date' => $data['advance_date'],
                'due_date' => $data['due_date'] ?? null,
                'total_amount' => $data['amount'],
                'description' => $data['description'] ?? 'Personel avansı',
                'notes' => $data['notes'] ?? null,
            ]);
            
            // Create payment (cash/bank movement)
            $paymentData = [
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'type' => $paymentType,
                'direction' => 'out',
                'party_id' => $party->id,
                'payment_date' => $data['advance_date'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? "Avans: {$advanceDocument->document_number}",
                'notes' => $data['notes'] ?? null,
                'reference_type' => Document::class,
                'reference_id' => $advanceDocument->id,
            ];
            
            // Set cashbox or bank_account based on payment source
            if ($data['payment_source_type'] === 'bank') {
                $paymentData['bank_account_id'] = $data['bank_account_id'];
            } else {
                $paymentData['cashbox_id'] = $data['cashbox_id'];
            }
            
            $payment = $this->paymentService->createPayment($paymentData);
            
            // IMPORTANT: Do NOT allocate payment to advance document
            // The payment represents cash movement, but the advance document remains OPEN
            // until deducted in payroll via internal_offset payment
            
            AuditLog::log($advanceDocument, 'create', null, [
                'action' => 'advance_given',
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
            ]);
            
            return [
                'advance_document_id' => $advanceDocument->id,
                'advance_document_number' => $advanceDocument->document_number,
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
            ];
        });
    }
    
    /**
     * Get open advances for an employee that can be deducted
     */
    public function suggestOpenAdvancesForEmployee(int $employeePartyId, $asOfDate = null): array
    {
        $asOfDate = $asOfDate ? \Carbon\Carbon::parse($asOfDate) : now();
        
        return Document::where('party_id', $employeePartyId)
            ->where('type', DocumentType::ADVANCE_GIVEN)
            ->where('direction', 'receivable')
            ->where('status', '!=', DocumentStatus::CANCELLED)
            ->where('status', '!=', DocumentStatus::REVERSED)
            ->where('document_date', '<=', $asOfDate->toDateString())
            ->orderBy('document_date')
            ->orderBy('document_number')
            ->get()
            ->map(function ($doc) {
                return [
                    'document_id' => $doc->id,
                    'document_number' => $doc->document_number,
                    'document_date' => $doc->document_date->format('Y-m-d'),
                    'total_amount' => $doc->total_amount,
                    'unpaid_amount' => $doc->unpaid_amount,
                    'paid_amount' => $doc->paid_amount,
                    'description' => $doc->description,
                ];
            })
            ->toArray();
    }
    
    /**
     * Apply advance deductions to payroll document
     * 
     * Creates:
     * - Internal offset payment (no cash movement)
     * - Allocations from internal_offset to:
     *   - advance_given documents (reduce employee owes)
     *   - salary_due document (reduce company owes)
     */
    public function applyAdvanceDeductionToPayroll(int $salaryDocumentId, array $deductions): array
    {
        return DB::transaction(function () use ($salaryDocumentId, $deductions) {
            // Validate salary document
            $salaryDocument = Document::findOrFail($salaryDocumentId);
            
            if ($salaryDocument->type !== DocumentType::PAYROLL_DUE) {
                throw new \Exception('Sadece maaş tahakkuku belgelerine avans kesintisi uygulanabilir.');
            }
            
            if ($salaryDocument->direction !== 'payable') {
                throw new \Exception('Maaş belgesi borç yönünde olmalıdır.');
            }
            
            // Validate period is open
            $this->periodService->validatePeriodOpen(
                $salaryDocument->company_id,
                $salaryDocument->document_date
            );
            
            $party = $salaryDocument->party;
            if ($party->type !== 'employee') {
                throw new \Exception('Belge bir personele ait olmalıdır.');
            }
            
            // Validate and collect advance documents
            $totalDeductionAmount = 0;
            $advanceDocuments = [];
            
            foreach ($deductions as $deduction) {
                $advanceDoc = Document::findOrFail($deduction['advance_document_id']);
                $deductionAmount = $deduction['amount'];
                
                // Validate advance document
                if ($advanceDoc->party_id !== $party->id) {
                    throw new \Exception("Avans belgesi farklı bir personele ait: {$advanceDoc->document_number}");
                }
                
                if ($advanceDoc->type !== DocumentType::ADVANCE_GIVEN) {
                    throw new \Exception("Geçersiz avans belgesi: {$advanceDoc->document_number}");
                }
                
                if ($advanceDoc->direction !== 'receivable') {
                    throw new \Exception("Avans belgesi alacak yönünde olmalıdır: {$advanceDoc->document_number}");
                }
                
                // Validate deduction amount
                if ($deductionAmount <= 0) {
                    throw new \Exception("Kesinti tutarı pozitif olmalıdır: {$advanceDoc->document_number}");
                }
                
                $unpaidAmount = $advanceDoc->unpaid_amount;
                if ($deductionAmount > $unpaidAmount) {
                    throw new \Exception(
                        "Kesinti tutarı avansın kalan borcundan fazla: {$advanceDoc->document_number} " .
                        "(Kalan: {$unpaidAmount}, Kesinti: {$deductionAmount})"
                    );
                }
                
                $advanceDocuments[] = [
                    'document' => $advanceDoc,
                    'amount' => $deductionAmount,
                ];
                
                $totalDeductionAmount += $deductionAmount;
            }
            
            if ($totalDeductionAmount <= 0) {
                throw new \Exception('Toplam kesinti tutarı pozitif olmalıdır.');
            }
            
            // Validate salary document has enough unpaid amount
            $salaryUnpaidAmount = $salaryDocument->unpaid_amount;
            if ($totalDeductionAmount > $salaryUnpaidAmount) {
                throw new \Exception(
                    "Toplam kesinti tutarı maaşın kalan borcundan fazla. " .
                    "(Kalan: {$salaryUnpaidAmount}, Kesinti: {$totalDeductionAmount})"
                );
            }
            
            // Create internal offset payment (no cash movement)
            $internalPayment = $this->paymentService->createPayment([
                'company_id' => $salaryDocument->company_id,
                'branch_id' => $salaryDocument->branch_id,
                'type' => PaymentType::INTERNAL_OFFSET,
                'direction' => 'internal',
                'party_id' => $party->id,
                'payment_date' => $salaryDocument->document_date,
                'amount' => $totalDeductionAmount,
                'description' => "Avans kesintisi: {$salaryDocument->document_number}",
                'notes' => 'İç mahsup - nakit hareketi yok',
                'reference_type' => Document::class,
                'reference_id' => $salaryDocument->id,
            ]);
            
            // Create allocations: internal_offset -> advance_given documents
            $allocations = [];
            foreach ($advanceDocuments as $advanceData) {
                $allocations[] = [
                    'document_id' => $advanceData['document']->id,
                    'amount' => $advanceData['amount'],
                    'allocation_date' => $salaryDocument->document_date,
                    'notes' => "Maaş kesintisi: {$salaryDocument->document_number}",
                ];
            }
            
            // Also allocate to salary document (reduces company owes employee)
            $allocations[] = [
                'document_id' => $salaryDocument->id,
                'amount' => $totalDeductionAmount,
                'allocation_date' => $salaryDocument->document_date,
                'notes' => 'Avans kesintisi',
            ];
            
            // Create all allocations
            $createdAllocations = $this->allocationService->allocate($internalPayment, $allocations);
            
            AuditLog::log($salaryDocument, 'update', null, [
                'action' => 'advance_deduction_applied',
                'deduction_amount' => $totalDeductionAmount,
                'internal_payment_id' => $internalPayment->id,
                'advance_documents' => array_map(fn($a) => $a['document']->id, $advanceDocuments),
            ]);
            
            return [
                'internal_payment_id' => $internalPayment->id,
                'internal_payment_number' => $internalPayment->payment_number,
                'total_deduction_amount' => $totalDeductionAmount,
                'allocations' => $createdAllocations,
            ];
        });
    }
}
