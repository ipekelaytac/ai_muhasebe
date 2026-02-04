<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Domain\Accounting\Models\Party;
use App\Services\CreateObligationService;
use App\Http\Controllers\Accounting\ReportController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AgingReportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $branch;
    protected $party;
    protected $createObligationService;

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
    }

    public function test_payables_aging_report_categorizes_correctly()
    {
        // Create documents with different due dates
        $this->createObligationService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type' => 'supplier_invoice',
            'direction' => 'payable',
            'party_id' => $this->party->id,
            'document_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(), // 5 days overdue
            'total_amount' => 100.00,
        ]);

        $this->createObligationService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type' => 'supplier_invoice',
            'direction' => 'payable',
            'party_id' => $this->party->id,
            'document_date' => now()->subDays(50)->toDateString(),
            'due_date' => now()->subDays(50)->toDateString(), // 50 days overdue
            'total_amount' => 200.00,
        ]);

        $this->createObligationService->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type' => 'supplier_invoice',
            'direction' => 'payable',
            'party_id' => $this->party->id,
            'document_date' => now()->subDays(100)->toDateString(),
            'due_date' => now()->subDays(100)->toDateString(), // 100 days overdue
            'total_amount' => 300.00,
        ]);

        $controller = new ReportController();
        $request = new \Illuminate\Http\Request([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'as_of_date' => now()->toDateString(),
        ]);

        $response = $controller->payablesAging($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(100.00, $data['buckets']['0-7']['amount']);
        $this->assertEquals(200.00, $data['buckets']['31-60']['amount']);
        $this->assertEquals(300.00, $data['buckets']['90+']['amount']);
        $this->assertEquals(600.00, $data['total']);
    }
}
