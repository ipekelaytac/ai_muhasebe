<?php

namespace Tests\Feature\Accounting;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Services\AllocationService;
use App\Domain\Accounting\Services\DocumentService;
use App\Domain\Accounting\Services\EmployeeAdvanceService;
use App\Domain\Accounting\Services\PaymentService;
use App\Domain\Accounting\Services\PeriodService;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSmokeTest extends TestCase
{
    use RefreshDatabase;
    
    protected Company $company;
    protected User $user;
    protected Party $customerParty;
    protected Party $supplierParty;
    protected Party $employeeParty;
    protected Cashbox $cashbox;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
        
        $this->customerParty = Party::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'customer',
        ]);
        
        $this->supplierParty = Party::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'supplier',
        ]);
        
        $this->employeeParty = Party::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'employee',
        ]);
        
        $this->cashbox = Cashbox::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
    }
    
    /** @test */
    public function can_load_main_accounting_pages()
    {
        $this->get(route('accounting.parties.index'))->assertStatus(200);
        $this->get(route('accounting.documents.index'))->assertStatus(200);
        $this->get(route('accounting.payments.index'))->assertStatus(200);
        $this->get(route('accounting.reports.index'))->assertStatus(200);
        $this->get(route('accounting.periods.index'))->assertStatus(200);
    }
    
    /** @test */
    public function can_create_supplier_invoice_document()
    {
        $documentService = app(DocumentService::class);
        
        $document = $documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplierParty->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'description' => 'Test supplier invoice',
        ]);
        
        $this->assertNotNull($document);
        $this->assertEquals('payable', $document->direction);
        $this->assertEquals(1000.00, $document->total_amount);
        $this->assertEquals(0, $document->paid_amount);
        $this->assertEquals(1000.00, $document->unpaid_amount);
    }
    
    /** @test */
    public function can_create_customer_invoice_document()
    {
        $documentService = app(DocumentService::class);
        
        $document = $documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::CUSTOMER_INVOICE,
            'party_id' => $this->customerParty->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 2000.00,
            'description' => 'Test customer invoice',
        ]);
        
        $this->assertNotNull($document);
        $this->assertEquals('receivable', $document->direction);
        $this->assertEquals(2000.00, $document->total_amount);
    }
    
    /** @test */
    public function can_record_payment()
    {
        $paymentService = app(PaymentService::class);
        
        $payment = $paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->customerParty->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
            'description' => 'Test payment',
        ]);
        
        $this->assertNotNull($payment);
        $this->assertEquals('in', $payment->direction);
        $this->assertEquals(500.00, $payment->amount);
        $this->assertEquals(500.00, $payment->unallocated_amount);
    }
    
    /** @test */
    public function can_allocate_payment_to_document()
    {
        $documentService = app(DocumentService::class);
        $paymentService = app(PaymentService::class);
        $allocationService = app(AllocationService::class);
        
        // Create document
        $document = $documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::CUSTOMER_INVOICE,
            'party_id' => $this->customerParty->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        // Create payment
        $payment = $paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->customerParty->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 600.00,
        ]);
        
        // Allocate partial
        $allocations = $allocationService->allocate($payment, [
            [
                'document_id' => $document->id,
                'amount' => 600.00,
            ],
        ]);
        
        $this->assertCount(1, $allocations);
        
        $document->refresh();
        $this->assertEquals(600.00, $document->paid_amount);
        $this->assertEquals(400.00, $document->unpaid_amount);
        $this->assertEquals('partial', $document->status);
    }
    
    /** @test */
    public function period_lock_blocks_document_update()
    {
        $periodService = app(PeriodService::class);
        $documentService = app(DocumentService::class);
        
        // Create document
        $document = $documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::CUSTOMER_INVOICE,
            'party_id' => $this->customerParty->id,
            'document_date' => now()->subMonth()->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        // Lock period
        $periodService->lockPeriod(
            $this->company->id,
            now()->subMonth()->year,
            now()->subMonth()->month
        );
        
        // Try to update - should fail
        $this->expectException(\Exception::class);
        
        $documentService->updateDocument($document, [
            'description' => 'Updated description',
        ]);
    }
    
    /** @test */
    public function can_give_employee_advance()
    {
        $advanceService = app(EmployeeAdvanceService::class);
        
        $result = $advanceService->giveAdvance([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'advance_date' => now()->toDateString(),
            'amount' => 500.00,
            'payment_source_type' => 'cash',
            'cashbox_id' => $this->cashbox->id,
        ]);
        
        $this->assertArrayHasKey('advance_document_id', $result);
        $this->assertArrayHasKey('payment_id', $result);
        
        $advanceDoc = Document::find($result['advance_document_id']);
        $this->assertEquals(DocumentType::ADVANCE_GIVEN, $advanceDoc->type);
        $this->assertEquals(500.00, $advanceDoc->unpaid_amount); // Should remain open
    }
    
    /** @test */
    public function can_apply_advance_deduction_to_payroll()
    {
        $advanceService = app(EmployeeAdvanceService::class);
        $documentService = app(DocumentService::class);
        
        // Create salary document
        $salaryDoc = $documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::PAYROLL_DUE,
            'party_id' => $this->employeeParty->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 5000.00,
        ]);
        
        // Create advance
        $advanceResult = $advanceService->giveAdvance([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'advance_date' => now()->subDays(10)->toDateString(),
            'amount' => 1000.00,
            'payment_source_type' => 'cash',
            'cashbox_id' => $this->cashbox->id,
        ]);
        
        $advanceDoc = Document::find($advanceResult['advance_document_id']);
        
        // Apply deduction
        $deductionResult = $advanceService->applyAdvanceDeductionToPayroll($salaryDoc->id, [
            [
                'advance_document_id' => $advanceDoc->id,
                'amount' => 1000.00,
            ],
        ]);
        
        $this->assertArrayHasKey('internal_payment_id', $deductionResult);
        
        $salaryDoc->refresh();
        $advanceDoc->refresh();
        
        $this->assertEquals(1000.00, $salaryDoc->paid_amount);
        $this->assertEquals(1000.00, $advanceDoc->paid_amount);
    }
}
