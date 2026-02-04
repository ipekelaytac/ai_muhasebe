<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Services\DocumentService;
use App\Domain\Accounting\Services\PaymentService;
use App\Domain\Accounting\Services\AllocationService;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Enums\PaymentType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AllocatePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $branch;
    protected $party;
    protected $documentService;
    protected $paymentService;
    protected $allocationService;

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
        $this->documentService = app(DocumentService::class);
        $this->paymentService = app(PaymentService::class);
        $this->allocationService = app(AllocationService::class);
    }

    public function test_can_allocate_payment_to_document()
    {
        // Create document
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
        ]);

        $cashbox = Cashbox::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        // First add cash
        $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->party->id,
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500.00,
        ]);

        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->party->id,
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 800.00,
        ]);

        $allocations = $this->allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 800.00],
        ]);

        $this->assertCount(1, $allocations);
        $this->assertEquals(800.00, $allocations[0]->amount);

        $document->refresh();
        $this->assertEquals(800.00, $document->allocated_amount);
        $this->assertEquals(200.00, $document->unpaid_amount);

        $payment->refresh();
        $this->assertEquals(800.00, $payment->allocated_amount);
        $this->assertEquals(0, $payment->unallocated_amount);
    }

    public function test_cannot_allocate_more_than_unpaid_amount()
    {
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
        ]);

        $cashbox = Cashbox::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->party->id,
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 2000.00,
        ]);

        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->party->id,
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('fazla'); // Turkish: "Dağıtım tutarı belgenin kalan borcundan fazla"

        $this->allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 1500.00], // More than document total (1000)
        ]);
    }

    public function test_cannot_allocate_more_than_payment_amount()
    {
        $document = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 2000.00,
        ]);

        $cashbox = Cashbox::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'type' => PaymentType::CASH_IN,
            'party_id' => $this->party->id,
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 2000.00,
        ]);

        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->party->id,
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('aşıyor'); // Turkish: "Dağıtım toplamı ödeme tutarını aşıyor"

        $this->allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 1000.00], // More than payment amount (500)
        ]);
    }
}
