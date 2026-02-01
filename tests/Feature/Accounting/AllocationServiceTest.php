<?php

namespace Tests\Feature\Accounting;

use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Services\AllocationService;
use App\Domain\Accounting\Services\DocumentService;
use App\Domain\Accounting\Services\PaymentService;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllocationServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected AllocationService $allocationService;
    protected DocumentService $documentService;
    protected PaymentService $paymentService;
    protected Company $company;
    protected Party $supplier;
    protected Party $customer;
    protected Cashbox $cashbox;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->allocationService = app(AllocationService::class);
        $this->documentService = app(DocumentService::class);
        $this->paymentService = app(PaymentService::class);
        
        $this->company = Company::create(['name' => 'Test Company']);
        
        $this->supplier = Party::create([
            'company_id' => $this->company->id,
            'type' => 'supplier',
            'code' => 'TED00001',
            'name' => 'Test Supplier',
        ]);
        
        $this->customer = Party::create([
            'company_id' => $this->company->id,
            'type' => 'customer',
            'code' => 'MUS00001',
            'name' => 'Test Customer',
        ]);
        
        $this->cashbox = Cashbox::create([
            'company_id' => $this->company->id,
            'code' => 'KASA-01',
            'name' => 'Ana Kasa',
            'is_active' => true,
            'opening_balance' => 50000.00,
        ]);
    }
    
    public function test_can_allocate_payment_to_single_document(): void
    {
        // Create a payable document
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        // Create an outgoing payment
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->supplier->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000.00,
        ]);
        
        // Allocate full amount
        $allocations = $this->allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 1000.00],
        ]);
        
        $this->assertCount(1, $allocations);
        $this->assertEquals(1000.00, $allocations[0]->amount);
        
        // Document should be settled
        $document->refresh();
        $this->assertEquals(DocumentStatus::SETTLED, $document->status);
        $this->assertEquals(0, $document->unpaid_amount);
        
        // Payment should be fully allocated
        $payment->refresh();
        $this->assertTrue($payment->is_fully_allocated);
    }
    
    public function test_can_allocate_partial_payment(): void
    {
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        // Pay only 400
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->supplier->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 400.00,
        ]);
        
        $this->allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 400.00],
        ]);
        
        $document->refresh();
        $this->assertEquals(DocumentStatus::PARTIAL, $document->status);
        $this->assertEquals(600.00, $document->unpaid_amount);
        $this->assertTrue($document->is_partial);
        $this->assertFalse($document->is_settled);
    }
    
    public function test_can_allocate_payment_to_multiple_documents(): void
    {
        $doc1 = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->subDays(10)->toDateString(),
            'total_amount' => 500.00,
        ]);
        
        $doc2 = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->subDays(5)->toDateString(),
            'total_amount' => 700.00,
        ]);
        
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->supplier->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000.00,
        ]);
        
        $allocations = $this->allocationService->allocate($payment, [
            ['document_id' => $doc1->id, 'amount' => 500.00],
            ['document_id' => $doc2->id, 'amount' => 500.00],
        ]);
        
        $this->assertCount(2, $allocations);
        
        $doc1->refresh();
        $doc2->refresh();
        
        $this->assertEquals(DocumentStatus::SETTLED, $doc1->status);
        $this->assertEquals(DocumentStatus::PARTIAL, $doc2->status);
        $this->assertEquals(200.00, $doc2->unpaid_amount);
    }
    
    public function test_cannot_allocate_more_than_document_unpaid(): void
    {
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->supplier->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 2000.00,
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('kalan borcu');
        
        $this->allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 1500.00],
        ]);
    }
    
    public function test_cannot_allocate_more_than_payment_amount(): void
    {
        $doc1 = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        $doc2 = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->supplier->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000.00,
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ödeme tutarını aşıyor');
        
        $this->allocationService->allocate($payment, [
            ['document_id' => $doc1->id, 'amount' => 600.00],
            ['document_id' => $doc2->id, 'amount' => 600.00],
        ]);
    }
    
    public function test_cannot_allocate_payment_to_wrong_direction_document(): void
    {
        // Create a payable document (we owe supplier)
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        // Try to allocate incoming payment (should be for receivables, not payables)
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->supplier->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000.00,
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('uyumsuz');
        
        $this->allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 1000.00],
        ]);
    }
    
    public function test_auto_allocate_uses_fifo(): void
    {
        // Create documents with different dates
        $oldDoc = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->subDays(30)->toDateString(),
            'due_date' => now()->subDays(30)->toDateString(),
            'total_amount' => 500.00,
        ]);
        
        $newDoc = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'total_amount' => 800.00,
        ]);
        
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->supplier->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 700.00,
        ]);
        
        $allocations = $this->allocationService->autoAllocate($payment);
        
        // Should allocate to old doc first (FIFO)
        $oldDoc->refresh();
        $newDoc->refresh();
        
        $this->assertEquals(DocumentStatus::SETTLED, $oldDoc->status);
        $this->assertEquals(DocumentStatus::PARTIAL, $newDoc->status);
        $this->assertEquals(600.00, $newDoc->unpaid_amount); // 800 - 200
    }
    
    public function test_can_handle_overpayment(): void
    {
        // Create a payable document (supplier invoice)
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        // Create an OUT payment (we pay supplier) - direction matches payable document
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->supplier->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500.00,
        ]);
        
        // Allocate 1000 to the payable document (direction matches: OUT payment → payable document)
        $this->allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 1000.00],
        ]);
        
        // Handle remaining 500 as overpayment
        // This creates ADVANCE_GIVEN (receivable - they owe us back)
        // Note: We cannot allocate OUT payment to RECEIVABLE document (direction mismatch)
        // So the advance document is created as a record, but payment remains partially unallocated
        $advanceDoc = $this->allocationService->handleOverpayment($payment, 500.00);
        
        $this->assertEquals(DocumentType::ADVANCE_GIVEN, $advanceDoc->type);
        $this->assertEquals(500.00, $advanceDoc->total_amount);
        $this->assertEquals('receivable', $advanceDoc->direction);
        
        // Payment has 500 unallocated (cannot allocate OUT to RECEIVABLE)
        // The advance document serves as a record of what they owe us back
        $payment->refresh();
        $this->assertEquals(500.00, $payment->unallocated_amount);
        $this->assertFalse($payment->is_fully_allocated);
        
        // Document should be settled
        $document->refresh();
        $this->assertEquals(DocumentStatus::SETTLED, $document->status);
    }
    
    public function test_can_cancel_allocation(): void
    {
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->supplier->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000.00,
        ]);
        
        $allocations = $this->allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 1000.00],
        ]);
        
        // Cancel the allocation
        $this->allocationService->cancelAllocation($allocations[0], 'Wrong allocation');
        
        $allocations[0]->refresh();
        $this->assertEquals('cancelled', $allocations[0]->status);
        
        // Document should go back to pending
        $document->refresh();
        $this->assertEquals(DocumentStatus::PENDING, $document->status);
        $this->assertEquals(1000.00, $document->unpaid_amount);
    }
}
