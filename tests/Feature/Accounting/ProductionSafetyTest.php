<?php

namespace Tests\Feature\Accounting;

use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Services\PeriodService;
use App\Models\FinanceTransaction;
use App\Models\CustomerTransaction;
use App\Models\PayrollPayment;
use App\Models\Advance;
use App\Models\Overtime;
use App\Models\EmployeeDebt;
use App\Models\EmployeeDebtPayment;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Party $party;
    protected Cashbox $cashbox;
    protected AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
        
        $this->party = Party::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'customer',
        ]);
        
        $this->cashbox = \App\Domain\Accounting\Models\Cashbox::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'year' => now()->year,
            'month' => now()->month,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => 'open',
        ]);
    }

    /** @test */
    public function old_finance_transaction_cannot_be_created()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('FinanceTransaction is deprecated');
        
        FinanceTransaction::create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'type' => 'expense',
            'category_id' => 1,
            'transaction_date' => now(),
            'amount' => 100,
        ]);
    }

    /** @test */
    public function old_customer_transaction_cannot_be_created()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CustomerTransaction is deprecated');
        
        CustomerTransaction::create([
            'customer_id' => 1,
            'company_id' => $this->company->id,
            'type' => 'income',
            'transaction_date' => now(),
            'amount' => 100,
        ]);
    }

    /** @test */
    public function old_payroll_payment_cannot_be_created()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('PayrollPayment is deprecated');
        
        PayrollPayment::create([
            'payroll_item_id' => 1,
            'payment_date' => now(),
            'amount' => 100,
            'method' => 'cash',
        ]);
    }

    /** @test */
    public function old_advance_cannot_be_created()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Advance is deprecated');
        
        Advance::create([
            'company_id' => $this->company->id,
            'employee_id' => 1,
            'advance_date' => now(),
            'amount' => 100,
        ]);
    }

    /** @test */
    public function old_overtime_cannot_be_created()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Overtime is deprecated');
        
        Overtime::create([
            'company_id' => $this->company->id,
            'employee_id' => 1,
            'overtime_date' => now(),
            'hours' => 2,
            'rate' => 50,
            'amount' => 100,
        ]);
    }

    /** @test */
    public function old_employee_debt_cannot_be_created()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('EmployeeDebt is deprecated');
        
        EmployeeDebt::create([
            'company_id' => $this->company->id,
            'employee_id' => 1,
            'debt_date' => now(),
            'amount' => 100,
        ]);
    }

    /** @test */
    public function document_cannot_be_created_in_locked_period()
    {
        $periodService = app(PeriodService::class);
        $periodService->lockPeriod($this->company->id, now()->year, now()->month);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('kilitli bir dönemde');
        
        $documentService = app(\App\Domain\Accounting\Services\DocumentService::class);
        $documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => 'expense_due',
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 100,
        ]);
    }

    /** @test */
    public function payment_cannot_be_created_in_locked_period()
    {
        $periodService = app(PeriodService::class);
        $periodService->lockPeriod($this->company->id, now()->year, now()->month);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('kilitli bir dönemde');
        
        $paymentService = app(\App\Domain\Accounting\Services\PaymentService::class);
        $paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => 'cash_out',
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100,
        ]);
    }

    /** @test */
    public function document_cannot_be_updated_in_locked_period()
    {
        $document = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'period_year' => now()->year,
            'period_month' => now()->month,
        ]);
        
        $periodService = app(PeriodService::class);
        $periodService->lockPeriod($this->company->id, now()->year, now()->month);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('locked period');
        
        $document->update(['description' => 'Updated']);
    }

    /** @test */
    public function payment_cannot_be_updated_in_locked_period()
    {
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'period_year' => now()->year,
            'period_month' => now()->month,
        ]);
        
        $periodService = app(PeriodService::class);
        $periodService->lockPeriod($this->company->id, now()->year, now()->month);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('locked period');
        
        $payment->update(['description' => 'Updated']);
    }

    /** @test */
    public function cash_balance_is_derived_from_payments_only()
    {
        // Create payments
        Payment::factory()->create([
            'company_id' => $this->company->id,
            'cashbox_id' => $this->cashbox->id,
            'direction' => 'in',
            'amount' => 1000,
            'net_amount' => 1000,
            'status' => 'confirmed',
        ]);
        
        Payment::factory()->create([
            'company_id' => $this->company->id,
            'cashbox_id' => $this->cashbox->id,
            'direction' => 'out',
            'amount' => 300,
            'net_amount' => 300,
            'status' => 'confirmed',
        ]);
        
        $this->cashbox->refresh();
        
        // Balance should be: opening_balance + 1000 - 300
        $expectedBalance = $this->cashbox->opening_balance + 1000 - 300;
        $this->assertEqualsWithDelta($expectedBalance, $this->cashbox->balance, 0.01);
    }

    /** @test */
    public function party_statement_balance_matches_calculated_balance()
    {
        // Create documents
        $doc1 = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->party->id,
            'direction' => 'receivable',
            'total_amount' => 1000,
            'status' => 'pending',
        ]);
        
        $doc2 = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->party->id,
            'direction' => 'payable',
            'total_amount' => 400,
            'status' => 'pending',
        ]);
        
        // Create payment and allocation
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->party->id,
            'direction' => 'in',
            'amount' => 300,
            'net_amount' => 300,
            'status' => 'confirmed',
        ]);
        
        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'document_id' => $doc1->id,
            'amount' => 300,
            'allocation_date' => now(),
            'status' => 'active',
        ]);
        
        $doc1->refresh();
        $this->party->refresh();
        
        // Balance should be: receivables (1000-300=700) - payables (400) = 300
        $expectedBalance = 700 - 400;
        $this->assertEqualsWithDelta($expectedBalance, $this->party->balance, 0.01);
    }

    /** @test */
    public function overpayment_creates_advance_document()
    {
        $document = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->party->id,
            'direction' => 'payable',
            'total_amount' => 1000,
            'status' => 'pending',
        ]);
        
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->party->id,
            'direction' => 'out',
            'amount' => 1500, // Overpayment
            'net_amount' => 1500,
            'status' => 'confirmed',
        ]);
        
        $allocationService = app(\App\Domain\Accounting\Services\AllocationService::class);
        
        // Allocate full document amount
        $allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 1000],
        ]);
        
        // Handle overpayment
        $overpaymentAmount = 500;
        $advanceDoc = $allocationService->handleOverpayment($payment, $overpaymentAmount);
        
        $this->assertInstanceOf(Document::class, $advanceDoc);
        $this->assertEquals('advance_received', $advanceDoc->type);
        $this->assertEquals(500, $advanceDoc->total_amount);
    }

    /** @test */
    public function allocation_constraints_are_enforced()
    {
        $document = Document::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->party->id,
            'direction' => 'payable',
            'total_amount' => 1000,
            'status' => 'pending',
        ]);
        
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $this->party->id,
            'direction' => 'out',
            'amount' => 500,
            'net_amount' => 500,
            'status' => 'confirmed',
        ]);
        
        $allocationService = app(\App\Domain\Accounting\Services\AllocationService::class);
        
        // Try to allocate more than document unpaid amount
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('kalan borcundan fazla');
        
        $allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 1500], // More than document total
        ]);
    }
}
