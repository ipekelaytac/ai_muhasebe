<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\Document;
use App\Models\PayrollItem;
use Illuminate\Support\Facades\DB;

/**
 * Service to create accounting Documents from PayrollItems
 */
class PayrollDocumentService
{
    protected DocumentService $documentService;
    
    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }
    
    /**
     * Create accounting Documents for a PayrollItem (ONE per installment)
     * 
     * Creates TWO documents:
     * - payroll_due_installment_1 (for 5'i)
     * - payroll_due_installment_2 (for 20'si)
     * 
     * @param PayrollItem $payrollItem
     * @return array ['installment_1' => Document, 'installment_2' => Document]
     * @throws \Exception
     */
    public function createDocumentsForPayrollItem(PayrollItem $payrollItem): array
    {
        return DB::transaction(function () use ($payrollItem) {
            $employee = $payrollItem->employee;
            $period = $payrollItem->payrollPeriod;
            
            // Ensure employee has party_id
            if (!$employee->party_id) {
                throw new \Exception("Employee {$employee->id} does not have a Party record. Please run: php artisan accounting:backfill-employee-parties");
            }
            
            // Get installments (must exist before creating documents)
            $installments = $payrollItem->installments()->orderBy('installment_no')->get();
            if ($installments->count() !== 2) {
                throw new \Exception("PayrollItem {$payrollItem->id} must have exactly 2 installments before creating documents.");
            }
            
            $installment1 = $installments->where('installment_no', 1)->first();
            $installment2 = $installments->where('installment_no', 2)->first();
            
            // Document date = period end date
            $documentDate = \Carbon\Carbon::create($period->year, $period->month, 1)->endOfMonth();
            
            $periodLabel = $period->year . '/' . str_pad($period->month, 2, '0', STR_PAD_LEFT);
            
            // Create Document for Installment 1 (5'i)
            $document1Data = [
                'company_id' => $payrollItem->payrollPeriod->company_id,
                'branch_id' => $payrollItem->payrollPeriod->branch_id,
                'type' => DocumentType::PAYROLL_DUE,
                'direction' => 'payable', // Company owes employee
                'party_id' => $employee->party_id,
                'document_date' => $documentDate->toDateString(),
                'due_date' => $installment1->due_date->toDateString(),
                'total_amount' => $installment1->planned_amount,
                'status' => DocumentStatus::PENDING,
                'source_type' => \App\Models\PayrollInstallment::class,
                'source_id' => $installment1->id,
                'description' => "Maaş Tahakkuku ({$installment1->title}) - {$periodLabel} - {$employee->full_name}",
                'notes' => "Bordro Kalemi ID: {$payrollItem->id}\n" .
                          "Taksit: {$installment1->installment_no} ({$installment1->title})\n" .
                          "Planlanan Tutar: " . number_format($installment1->planned_amount, 2) . " ₺",
            ];
            
            $document1 = $this->documentService->createDocument($document1Data);
            $installment1->update(['accounting_document_id' => $document1->id]);
            
            // Create Document for Installment 2 (20'si)
            $document2Data = [
                'company_id' => $payrollItem->payrollPeriod->company_id,
                'branch_id' => $payrollItem->payrollPeriod->branch_id,
                'type' => DocumentType::PAYROLL_DUE,
                'direction' => 'payable',
                'party_id' => $employee->party_id,
                'document_date' => $documentDate->toDateString(),
                'due_date' => $installment2->due_date->toDateString(),
                'total_amount' => $installment2->planned_amount,
                'status' => DocumentStatus::PENDING,
                'source_type' => \App\Models\PayrollInstallment::class,
                'source_id' => $installment2->id,
                'description' => "Maaş Tahakkuku ({$installment2->title}) - {$periodLabel} - {$employee->full_name}",
                'notes' => "Bordro Kalemi ID: {$payrollItem->id}\n" .
                          "Taksit: {$installment2->installment_no} ({$installment2->title})\n" .
                          "Planlanan Tutar: " . number_format($installment2->planned_amount, 2) . " ₺",
            ];
            
            $document2 = $this->documentService->createDocument($document2Data);
            $installment2->update(['accounting_document_id' => $document2->id]);
            
            // Keep document_id on PayrollItem pointing to first document for backward compatibility
            $payrollItem->update(['document_id' => $document1->id]);
            
            return [
                'installment_1' => $document1,
                'installment_2' => $document2,
            ];
        });
    }
    
    /**
     * @deprecated Use createDocumentsForPayrollItem() instead
     * Creates single document (legacy method, kept for backward compatibility)
     */
    public function createDocumentForPayrollItem(PayrollItem $payrollItem): Document
    {
        $documents = $this->createDocumentsForPayrollItem($payrollItem);
        return $documents['installment_1']; // Return first document for backward compatibility
    }
    
    /**
     * Create documents for all PayrollItems in a period that don't have documents yet
     * 
     * @param \App\Models\PayrollPeriod $period
     * @return array ['created' => int, 'skipped' => int, 'errors' => array]
     */
    public function createDocumentsForPeriod(\App\Models\PayrollPeriod $period): array
    {
        $result = ['created' => 0, 'skipped' => 0, 'errors' => []];
        
        $items = PayrollItem::where('payroll_period_id', $period->id)
            ->whereNull('document_id')
            ->with(['employee', 'payrollPeriod', 'installments'])
            ->get();
        
        foreach ($items as $item) {
            try {
                $this->createDocumentForPayrollItem($item);
                $result['created']++;
            } catch (\Exception $e) {
                $result['skipped']++;
                $result['errors'][] = [
                    'payroll_item_id' => $item->id,
                    'employee' => $item->employee->full_name ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $result;
    }
}
