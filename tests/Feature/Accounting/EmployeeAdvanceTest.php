<?php

namespace Tests\Feature\Accounting;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Services\EmployeeAdvanceService;
use App\Domain\Accounting\Services\PeriodService;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAdvanceTest extends TestCase
{
    use RefreshDatabase;
    
    protected EmployeeAdvanceService $advanceService;
    protected Company $company;
    protected Party $employeeParty;
    protected Cashbox $cashbox;
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
        
        // Create employee party
        $this->employeeParty = Party::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'employee',
            'name' => 'Test Employee',
        ]);
        
        // Create cashbox
        $this->cashbox = Cashbox::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        
        $this->advanceService = app(EmployeeAdvanceService::class);
    }
    
    /** @test */
    public function it_can_give_advance_to_employee()
    {
        $result = $this->advanceService->giveAdvance([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'advance_date' => now()->toDateString(),
            'amount' => 1000.00,
            'payment_source_type' => 'cash',
            'cashbox_id' => $this->cashbox->id,
            'description' => 'Test advance',
        ]);
        
        // Assert advance document created
        $advanceDocument = Document::find($result['advance_document_id']);
        $this->assertNotNull($advanceDocument);
        $this->assertEquals(DocumentType::ADVANCE_GIVEN, $advanceDocument->type);
        $this->assertEquals('receivable', $advanceDocument->direction);
        $this->assertEquals(1000.00, $advanceDocument->total_amount);
        $this->assertEquals($this->employeeParty->id, $advanceDocument->party_id);
        
        // Assert payment created
        $payment = Payment::find($result['payment_id']);
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentType::CASH_OUT, $payment->type);
        $this->assertEquals('out', $payment->direction);
        $this->assertEquals(1000.00, $payment->amount);
        $this->assertEquals($this->cashbox->id, $payment->cashbox_id);
        $this->assertEquals(Document::class, $payment->reference_type);
        $this->assertEquals($advanceDocument->id, $payment->reference_id);
        
        // Assert advance document is OPEN (not allocated)
        $this->assertEquals(0, $advanceDocument->paid_amount);
        $this->assertEquals(1000.00, $advanceDocument->unpaid_amount);
        $this->assertTrue($advanceDocument->is_open);
    }
    
    /** @test */
    public function it_cannot_give_advance_to_non_employee()
    {
        $customerParty = Party::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'customer',
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Sadece personellere avans verilebilir.');
        
        $this->advanceService->giveAdvance([
            'company_id' => $this->company->id,
            'party_id' => $customerParty->id,
            'advance_date' => now()->toDateString(),
            'amount' => 1000.00,
            'payment_source_type' => 'cash',
            'cashbox_id' => $this->cashbox->id,
        ]);
    }
    
    /** @test */
    public function it_can_suggest_open_advances_for_employee()
    {
        // Create advance documents
        $advance1 = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'type' => DocumentType::ADVANCE_GIVEN,
            'direction' => 'receivable',
            'total_amount' => 500.00,
            'document_date' => now()->subDays(10),
        ]);
        
        $advance2 = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'type' => DocumentType::ADVANCE_GIVEN,
            'direction' => 'receivable',
            'total_amount' => 300.00,
            'document_date' => now()->subDays(5),
        ]);
        
        // Create settled advance (should not appear)
        $advance3 = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'type' => DocumentType::ADVANCE_GIVEN,
            'direction' => 'receivable',
            'total_amount' => 200.00,
            'status' => 'settled',
            'document_date' => now()->subDays(15),
        ]);
        
        $suggestions = $this->advanceService->suggestOpenAdvancesForEmployee($this->employeeParty->id);
        
        $this->assertCount(2, $suggestions);
        $this->assertEquals($advance1->id, $suggestions[0]['document_id']);
        $this->assertEquals($advance2->id, $suggestions[1]['document_id']);
    }
    
    /** @test */
    public function it_can_apply_advance_deduction_to_payroll()
    {
        // Create salary document
        $salaryDocument = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'type' => DocumentType::PAYROLL_DUE,
            'direction' => 'payable',
            'total_amount' => 5000.00,
            'document_date' => now()->toDateString(),
        ]);
        
        // Create advance documents
        $advance1 = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'type' => DocumentType::ADVANCE_GIVEN,
            'direction' => 'receivable',
            'total_amount' => 1000.00,
            'document_date' => now()->subDays(10),
        ]);
        
        $advance2 = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'type' => DocumentType::ADVANCE_GIVEN,
            'direction' => 'receivable',
            'total_amount' => 500.00,
            'document_date' => now()->subDays(5),
        ]);
        
        $result = $this->advanceService->applyAdvanceDeductionToPayroll($salaryDocument->id, [
            [
                'advance_document_id' => $advance1->id,
                'amount' => 1000.00,
            ],
            [
                'advance_document_id' => $advance2->id,
                'amount' => 500.00,
            ],
        ]);
        
        // Assert internal offset payment created
        $internalPayment = Payment::find($result['internal_payment_id']);
        $this->assertNotNull($internalPayment);
        $this->assertEquals(PaymentType::INTERNAL_OFFSET, $internalPayment->type);
        $this->assertEquals('internal', $internalPayment->direction);
        $this->assertEquals(1500.00, $internalPayment->amount);
        $this->assertNull($internalPayment->cashbox_id);
        $this->assertNull($internalPayment->bank_account_id);
        
        // Assert allocations created
        $this->assertCount(3, $result['allocations']); // 2 for advances + 1 for salary
        
        // Refresh documents
        $advance1->refresh();
        $advance2->refresh();
        $salaryDocument->refresh();
        
        // Assert advances are settled
        $this->assertEquals(1000.00, $advance1->paid_amount);
        $this->assertEquals(0, $advance1->unpaid_amount);
        $this->assertEquals(500.00, $advance2->paid_amount);
        $this->assertEquals(0, $advance2->unpaid_amount);
        
        // Assert salary document is reduced
        $this->assertEquals(1500.00, $salaryDocument->paid_amount);
        $this->assertEquals(3500.00, $salaryDocument->unpaid_amount);
    }
    
    /** @test */
    public function it_validates_deduction_amount_does_not_exceed_advance_unpaid()
    {
        $salaryDocument = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'type' => DocumentType::PAYROLL_DUE,
            'direction' => 'payable',
            'total_amount' => 5000.00,
        ]);
        
        $advance = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'type' => DocumentType::ADVANCE_GIVEN,
            'direction' => 'receivable',
            'total_amount' => 1000.00,
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kesinti tutarı avansın kalan borcundan fazla');
        
        $this->advanceService->applyAdvanceDeductionToPayroll($salaryDocument->id, [
            [
                'advance_document_id' => $advance->id,
                'amount' => 1500.00, // More than unpaid
            ],
        ]);
    }
    
    /** @test */
    public function it_validates_deduction_amount_does_not_exceed_salary_unpaid()
    {
        $salaryDocument = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'type' => DocumentType::PAYROLL_DUE,
            'direction' => 'payable',
            'total_amount' => 1000.00, // Small salary
        ]);
        
        $advance = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->employeeParty->id,
            'type' => DocumentType::ADVANCE_GIVEN,
            'direction' => 'receivable',
            'total_amount' => 2000.00,
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Toplam kesinti tutarı maaşın kalan borcundan fazla');
        
        $this->advanceService->applyAdvanceDeductionToPayroll($salaryDocument->id, [
            [
                'advance_document_id' => $advance->id,
                'amount' => 1500.00, // More than salary unpaid
            ],
        ]);
    }
}
