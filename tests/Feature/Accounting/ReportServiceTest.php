<?php

namespace Tests\Feature\Accounting;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\BankAccount;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\AllocationService;
use App\Domain\Accounting\Services\DocumentService;
use App\Domain\Accounting\Services\PaymentService;
use App\Domain\Accounting\Services\ReportService;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected ReportService $reportService;
    protected DocumentService $documentService;
    protected PaymentService $paymentService;
    protected AllocationService $allocationService;
    protected Company $company;
    protected Party $supplier;
    protected Party $customer;
    protected Cashbox $cashbox;
    protected BankAccount $bankAccount;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reportService = app(ReportService::class);
        $this->documentService = app(DocumentService::class);
        $this->paymentService = app(PaymentService::class);
        $this->allocationService = app(AllocationService::class);
        
        $this->company = Company::create(['name' => 'Test Company']);
        
        $this->supplier = Party::create([
            'company_id' => $this->company->id,
            'type' => 'supplier',
            'code' => 'TED00001',
            'name' => 'Test Supplier',
        ]);
        
        $this->customer = Party::create([
            'company_id' => $this->company->id,
            'type' => 'customer',
            'code' => 'MUS00001',
            'name' => 'Test Customer',
        ]);
        
        $this->cashbox = Cashbox::create([
            'company_id' => $this->company->id,
            'code' => 'KASA-01',
            'name' => 'Ana Kasa',
            'is_active' => true,
            'opening_balance' => 10000.00,
        ]);
        
        $this->bankAccount = BankAccount::create([
            'company_id' => $this->company->id,
            'code' => 'BANKA-01',
            'name' => 'Ana Banka',
            'bank_name' => 'Test Bank',
            'is_active' => true,
            'opening_balance' => 50000.00,
        ]);
    }
    
    public function test_cash_bank_balance_report(): void
    {
        // Add some transactions
        $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_IN,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 5000.00,
        ]);
        
        $report = $this->reportService->getCashBankBalances($this->company->id);
        
        $this->assertEquals(now()->toDateString(), $report['as_of_date']);
        $this->assertCount(1, $report['cashboxes']);
        $this->assertCount(1, $report['bank_accounts']);
        $this->assertEquals(15000.00, $report['total_cash']); // 10000 + 5000
        $this->assertEquals(50000.00, $report['total_bank']);
        $this->assertEquals(65000.00, $report['total']);
    }
    
    public function test_payables_aging_report(): void
    {
        // Create documents with different due dates
        $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->subDays(100)->toDateString(),
            'due_date' => now()->subDays(100)->toDateString(), // 100+ days overdue
            'total_amount' => 1000.00,
        ]);
        
        $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(20)->toDateString(), // 20 days overdue (8-30 bucket)
            'total_amount' => 500.00,
        ]);
        
        $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(), // Not yet due
            'total_amount' => 300.00,
        ]);
        
        $report = $this->reportService->getAgingReport($this->company->id, 'payable');
        
        $this->assertEquals('payable', $report['direction']);
        $this->assertEquals(1800.00, $report['total']);
        $this->assertEquals(300.00, $report['summary']['current']['amount']);
        $this->assertEquals(500.00, $report['summary']['8_30']['amount']);
        $this->assertEquals(1000.00, $report['summary']['90_plus']['amount']);
        
        $this->assertCount(1, $report['by_party']);
        $this->assertEquals($this->supplier->id, $report['by_party'][0]['party_id']);
    }
    
    public function test_party_statement_report(): void
    {
        // Create some documents and payments
        $doc = $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->subDays(10)->toDateString(),
            'total_amount' => 1000.00,
        ]);
        
        $payment = $this->paymentService->createPayment([
            'company_id' => $this->company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $this->supplier->id,
            'cashbox_id' => $this->cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 600.00,
        ]);
        
        $this->allocationService->allocate($payment, [
            ['document_id' => $doc->id, 'amount' => 600.00],
        ]);
        
        $statement = $this->reportService->getPartyStatement($this->supplier->id);
        
        $this->assertEquals($this->supplier->id, $statement['party']['id']);
        $this->assertCount(2, $statement['lines']); // 1 document + 1 payment
        $this->assertEquals(-400.00, $statement['closing_balance']); // We still owe 400
    }
    
    public function test_monthly_pnl_report(): void
    {
        // Create income document
        $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::CUSTOMER_INVOICE,
            'party_id' => $this->customer->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 5000.00,
        ]);
        
        // Create expense document
        $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 2000.00,
        ]);
        
        $pnl = $this->reportService->getMonthlyPnL(
            $this->company->id,
            now()->year,
            now()->month
        );
        
        $this->assertEquals(now()->year, $pnl['period']['year']);
        $this->assertEquals(now()->month, $pnl['period']['month']);
        $this->assertEquals(5000.00, $pnl['summary']['total_income']);
        $this->assertEquals(2000.00, $pnl['summary']['total_expenses']);
        $this->assertEquals(3000.00, $pnl['summary']['net_income']);
    }
    
    public function test_cashflow_forecast_report(): void
    {
        // Create receivable due in 15 days
        $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::CUSTOMER_INVOICE,
            'party_id' => $this->customer->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'total_amount' => 3000.00,
        ]);
        
        // Create payable due in 45 days
        $this->documentService->createDocument([
            'company_id' => $this->company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $this->supplier->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(45)->toDateString(),
            'total_amount' => 1500.00,
        ]);
        
        $forecast = $this->reportService->getCashflowForecast($this->company->id, 90);
        
        $this->assertEquals(60000.00, $forecast['current_balance']); // 10000 cash + 50000 bank
        $this->assertCount(1, $forecast['inflows']);
        $this->assertCount(1, $forecast['outflows']);
        $this->assertEquals(3000.00, $forecast['total_inflows']);
        $this->assertEquals(1500.00, $forecast['total_outflows']);
        
        // 30 day projection should include the receivable but not the payable
        $this->assertEquals(
            60000.00 + 3000.00,
            $forecast['periods']['30_days']['projected_balance']
        );
        
        // 60 day projection should include both
        $this->assertEquals(
            60000.00 + 3000.00 - 1500.00,
            $forecast['periods']['60_days']['projected_balance']
        );
    }
}
