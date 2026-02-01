<?php

namespace Tests\Feature\Accounting;

use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\DocumentService;
use App\Domain\Accounting\Services\PeriodService;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected DocumentService $documentService;
    protected Company $company;
    protected Party $party;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->documentService = app(DocumentService::class);
        
        // Create test company
        $this->company = Company::create([
            'name' => 'Test Company',
        ]);
        
        // Create test party
        $this->party = Party::create([
            'company_id' => $this->company->id,
            'type' => 'supplier',
            'code' => 'TED00001',
            'name' => 'Test Supplier',
        ]);
    }
    
    public function test_can_create_supplier_invoice_document(): void
    {
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
            'description' => 'Test supplier invoice',
        ]);
        
        $this->assertNotNull($document->id);
        $this->assertEquals(DocumentType::SUPPLIER_INVOICE, $document->type);
        $this->assertEquals('payable', $document->direction);
        $this->assertEquals(DocumentStatus::PENDING, $document->status);
        $this->assertEquals(1000.00, $document->total_amount);
        $this->assertEquals(1000.00, $document->unpaid_amount);
        $this->assertStringStartsWith('AF', $document->document_number);
    }
    
    public function test_can_create_customer_invoice_document(): void
    {
        $customer = Party::create([
            'company_id' => $this->company->id,
            'type' => 'customer',
            'code' => 'MUS00001',
            'name' => 'Test Customer',
        ]);
        
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::CUSTOMER_INVOICE,
            'party_id' => $customer->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 2500.00,
        ]);
        
        $this->assertEquals('receivable', $document->direction);
        $this->assertStringStartsWith('SF', $document->document_number);
    }
    
    public function test_document_unpaid_amount_is_calculated_correctly(): void
    {
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        // Initially unpaid = total
        $this->assertEquals(1000.00, $document->unpaid_amount);
        $this->assertFalse($document->is_settled);
        $this->assertFalse($document->is_partial);
    }
    
    public function test_cannot_create_document_in_locked_period(): void
    {
        // Lock the current period
        $periodService = app(PeriodService::class);
        $periodService->lockPeriod($this->company->id, now()->year, now()->month);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('kilitli');
        
        $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
    }
    
    public function test_can_cancel_document_without_allocations(): void
    {
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        $cancelled = $this->documentService->cancelDocument($document, 'Test cancellation');
        
        $this->assertEquals(DocumentStatus::CANCELLED, $cancelled->status);
        $this->assertStringContainsString('Test cancellation', $cancelled->notes);
    }
    
    public function test_can_reverse_document(): void
    {
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->party->id,
            'document_date' => now()->subDays(5)->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        $reversal = $this->documentService->reverseDocument($document, 'Wrong entry');
        
        $this->assertEquals(DocumentStatus::REVERSED, $reversal->status);
        $this->assertEquals(-1000.00, $reversal->total_amount);
        $this->assertEquals($document->id, $reversal->reversed_document_id);
        
        // Original should be marked as reversed
        $document->refresh();
        $this->assertEquals(DocumentStatus::REVERSED, $document->status);
        $this->assertEquals($reversal->id, $document->reversal_document_id);
    }
    
    public function test_document_with_lines(): void
    {
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1180.00,
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'lines' => [
                [
                    'description' => 'Product A',
                    'quantity' => 10,
                    'unit' => 'adet',
                    'unit_price' => 50.00,
                    'tax_rate' => 18,
                ],
                [
                    'description' => 'Product B',
                    'quantity' => 5,
                    'unit' => 'adet',
                    'unit_price' => 100.00,
                    'tax_rate' => 18,
                ],
            ],
        ]);
        
        $this->assertCount(2, $document->lines);
        $this->assertEquals('Product A', $document->lines[0]->description);
        $this->assertEquals(10, $document->lines[0]->quantity);
    }
}
