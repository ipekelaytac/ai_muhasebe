<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Party;
use App\Models\Document;
use App\Models\AccountingPeriod;
use App\Services\CreateObligationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateObligationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $branch;
    protected $party;
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
            'type' => 'supplier',
        ]);

        $this->actingAs($this->user);
        $this->service = app(CreateObligationService::class);
    }

    public function test_can_create_supplier_invoice()
    {
        $data = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type' => 'supplier_invoice',
            'direction' => 'payable',
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
            'description' => 'Test supplier invoice',
        ];

        $document = $this->service->create($data);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('supplier_invoice', $document->document_type);
        $this->assertEquals('payable', $document->direction);
        $this->assertEquals(1000.00, $document->total_amount);
        $this->assertEquals(0, $document->paid_amount);
        $this->assertEquals(1000.00, $document->unpaid_amount);
        $this->assertEquals('pending', $document->status); // Schema uses 'pending' as default, not 'posted'
    }

    public function test_cannot_create_document_in_locked_period()
    {
        // Create and lock period (periods are company-level only, no branch_id)
        $period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'year' => now()->year,
            'month' => now()->month,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => 'locked',
            'locked_by' => $this->user->id,
            'locked_at' => now(),
        ]);

        $data = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type' => 'supplier_invoice',
            'direction' => 'payable',
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot create document in locked period');

        $this->service->create($data);
    }

    public function test_document_number_is_generated_if_not_provided()
    {
        $data = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type' => 'supplier_invoice',
            'direction' => 'payable',
            'party_id' => $this->party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
        ];

        $document = $this->service->create($data);

        $this->assertNotNull($document->document_number);
        $this->assertStringStartsWith('SUP-', $document->document_number);
    }
}
