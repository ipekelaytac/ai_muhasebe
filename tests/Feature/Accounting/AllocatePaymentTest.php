<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Party;
use App\Models\Document;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\CreateObligationService;
use App\Services\RecordPaymentService;
use App\Services\AllocatePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AllocatePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $branch;
    protected $party;
    protected $createObligationService;
    protected $recordPaymentService;
    protected $allocatePaymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->party = Party::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'type' => 'supplier',
        ]);

        $this->actingAs($this->user);
        $this->createObligationService = app(CreateObligationService::class);
        $this->recordPaymentService = app(RecordPaymentService::class);
        $this->allocatePaymentService = app(AllocatePaymentService::class);
    }

    public function test_can_allocate_payment_to_document()
    {
        // Create document
        $document = $this->createObligationService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type' => 'supplier_invoice',
            'direction' => 'payable',
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
        ]);

        // Create payment
        $cashbox = \App\Models\Cashbox::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        // First add cash
        $this->recordPaymentService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'payment_type' => 'cash_in',
            'direction' => 'inflow',
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500.00,
        ]);

        $payment = $this->recordPaymentService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'payment_type' => 'cash_out',
            'direction' => 'outflow',
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 800.00,
        ]);

        // Allocate payment to document
        $allocations = $this->allocatePaymentService->allocate($payment, [
            [
                'document_id' => $document->id,
                'amount' => 800.00,
            ],
        ]);

        $this->assertCount(1, $allocations);
        $this->assertEquals(800.00, $allocations[0]->amount);

        // Verify document updated
        $document->refresh();
        $this->assertEquals(800.00, $document->paid_amount);
        $this->assertEquals(200.00, $document->unpaid_amount);

        // Verify payment updated
        $payment->refresh();
        $this->assertEquals(800.00, $payment->allocated_amount);
        $this->assertEquals(0, $payment->unallocated_amount);
    }

    public function test_cannot_allocate_more_than_unpaid_amount()
    {
        $document = $this->createObligationService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type' => 'supplier_invoice',
            'direction' => 'payable',
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
        ]);

        $cashbox = \App\Models\Cashbox::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->recordPaymentService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'payment_type' => 'cash_in',
            'direction' => 'inflow',
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 2000.00,
        ]);

        $payment = $this->recordPaymentService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'payment_type' => 'cash_out',
            'direction' => 'outflow',
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('exceeds unpaid amount');

        $this->allocatePaymentService->allocate($payment, [
            [
                'document_id' => $document->id,
                'amount' => 1500.00, // More than document total (1000)
            ],
        ]);
    }

    public function test_cannot_allocate_more_than_payment_amount()
    {
        $document = $this->createObligationService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type' => 'supplier_invoice',
            'direction' => 'payable',
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 2000.00,
        ]);

        $cashbox = \App\Models\Cashbox::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->recordPaymentService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'payment_type' => 'cash_in',
            'direction' => 'inflow',
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 2000.00,
        ]);

        $payment = $this->recordPaymentService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'payment_type' => 'cash_out',
            'direction' => 'outflow',
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('exceeds unallocated amount');

        $this->allocatePaymentService->allocate($payment, [
            [
                'document_id' => $document->id,
                'amount' => 1000.00, // More than payment amount (500)
            ],
        ]);
    }
}
