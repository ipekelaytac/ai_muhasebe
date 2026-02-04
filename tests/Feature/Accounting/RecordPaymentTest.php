<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Payment;
use App\Services\RecordPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecordPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $branch;
    protected $party;
    protected $cashbox;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->party = Party::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->cashbox = Cashbox::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user);
        $this->service = app(RecordPaymentService::class);
    }

    public function test_can_record_cash_in_payment()
    {
        $data = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'payment_type' => 'cash_in',
            'direction' => 'inflow',
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
            'description' => 'Test cash receipt',
        ];

        $payment = $this->service->create($data);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('cash_in', $payment->type);
        $this->assertEquals('in', $payment->direction);
        $this->assertEquals(500.00, $payment->amount);
        $this->assertEquals(0, $payment->allocated_amount);
        $this->assertEquals(500.00, $payment->unallocated_amount);
    }

    public function test_cannot_record_outflow_with_insufficient_balance()
    {
        // Create payment that exceeds balance
        $data = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'payment_type' => 'cash_out',
            'direction' => 'outflow',
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000.00, // More than available balance (0)
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Yetersiz'); // Turkish: "Yetersiz kasa bakiyesi"

        $this->service->create($data);
    }

    public function test_can_record_payment_after_inflow()
    {
        // First, record an inflow
        $inflowData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'payment_type' => 'cash_in',
            'direction' => 'inflow',
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000.00,
        ];

        $this->service->create($inflowData);

        // Now record outflow
        $outflowData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'payment_type' => 'cash_out',
            'direction' => 'outflow',
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
        ];

        $payment = $this->service->create($outflowData);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(500.00, $payment->amount);
    }
}
