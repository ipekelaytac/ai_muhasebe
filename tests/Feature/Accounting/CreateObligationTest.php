<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Services\PeriodService;
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
        $this->assertEquals('supplier_invoice', $document->type);
        $this->assertEquals('payable', $document->direction);
        $this->assertEquals(1000.00, $document->total_amount);
        $this->assertEquals(0, $document->allocated_amount);
        $this->assertEquals(1000.00, $document->unpaid_amount);
        $this->assertEquals('pending', $document->status);
    }

    public function test_cannot_create_document_in_locked_period()
    {
        $periodService = app(PeriodService::class);
        $periodService->lockPeriod($this->company->id, now()->year, now()->month, 'Test lock');

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
        $this->expectExceptionMessage('kilitli'); // Turkish: "Bu tarih kilitli bir dönemde"

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
        // Domain uses AF (Alım Faturası) prefix for supplier_invoice
        $this->assertStringStartsWith('AF', $document->document_number);
    }
}
