<?php

namespace Tests\Feature\Accounting;

use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Services\PaymentService;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected PaymentService $paymentService;
    protected Company $company;
    protected Party $party;
    protected Cashbox $cashbox;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->paymentService = app(PaymentService::class);
        
        $this->company = Company::create([
            'name' => 'Test Company',
        ]);
        
        $this->party = Party::create([
            'company_id' => $this->company->id,
            'type' => 'supplier',
            'code' => 'TED00001',
            'name' => 'Test Supplier',
        ]);
        
        $this->cashbox = Cashbox::create([
            'company_id' => $this->company->id,
            'code' => 'KASA-01',
            'name' => 'Ana Kasa',
            'currency' => 'TRY',
            'is_active' => true,
            'is_default' => true,
            'opening_balance' => 10000.00,
        ]);
    }
    
    public function test_can_create_cash_in_payment(): void
    {
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->party->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
            'description' => 'Test cash receipt',
        ]);
        
        $this->assertNotNull($payment->id);
        $this->assertEquals(PaymentType::CASH_IN, $payment->type);
        $this->assertEquals('in', $payment->direction);
        $this->assertEquals('confirmed', $payment->status);
        $this->assertEquals(500.00, $payment->amount);
        $this->assertEquals(500.00, $payment->net_amount);
        $this->assertStringStartsWith('KG', $payment->payment_number);
    }
    
    public function test_can_create_cash_out_payment(): void
    {
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->party->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 300.00,
        ]);
        
        $this->assertEquals('out', $payment->direction);
        $this->assertStringStartsWith('KC', $payment->payment_number);
    }
    
    public function test_payment_with_fee_calculates_net_amount(): void
    {
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->party->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000.00,
            'fee_amount' => 25.00,
        ]);
        
        $this->assertEquals(1000.00, $payment->amount);
        $this->assertEquals(25.00, $payment->fee_amount);
        $this->assertEquals(975.00, $payment->net_amount);
    }
    
    public function test_cannot_create_cash_out_with_insufficient_balance(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Yetersiz kasa bakiyesi');
        
        $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->party->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 50000.00, // More than opening balance
        ]);
    }
    
    public function test_cannot_create_cash_payment_without_cashbox(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('kasa seçilmeli');
        
        $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->party->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
        ]);
    }
    
    public function test_cashbox_balance_updates_correctly(): void
    {
        $initialBalance = $this->cashbox->balance;
        
        // Cash in
        $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->party->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000.00,
        ]);
        
        $this->cashbox->refresh();
        $this->assertEquals($initialBalance + 1000.00, $this->cashbox->balance);
        
        // Cash out
        $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->party->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 300.00,
        ]);
        
        $this->cashbox->refresh();
        $this->assertEquals($initialBalance + 700.00, $this->cashbox->balance);
    }
    
    public function test_can_cancel_payment_without_allocations(): void
    {
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->party->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
        ]);
        
        $cancelled = $this->paymentService->cancelPayment($payment, 'Wrong entry');
        
        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertStringContainsString('Wrong entry', $cancelled->notes);
    }
    
    public function test_can_reverse_payment(): void
    {
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->party->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
        ]);
        
        $reversal = $this->paymentService->reversePayment($payment, 'Correction');
        
        $this->assertEquals('reversed', $reversal->status);
        $this->assertEquals('out', $reversal->direction); // Opposite of original
        $this->assertEquals(500.00, $reversal->amount);
        $this->assertEquals($payment->id, $reversal->reversed_payment_id);
        
        // Original should be marked as reversed
        $payment->refresh();
        $this->assertEquals('reversed', $payment->status);
    }
}
