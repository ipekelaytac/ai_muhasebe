<?php

namespace Tests\Feature\Accounting;

use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\AllocationService;
use App\Domain\Accounting\Services\DocumentService;
use App\Domain\Accounting\Services\PaymentService;
use App\Domain\Accounting\Services\PeriodService;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test: allocate OPEN-period payment to LOCKED-period document.
 * Verifies status-only updates are allowed in locked periods.
 */
class AllocationLockRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocate_open_period_payment_to_locked_period_document_updates_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::factory()->create();

        $lastMonth = now()->subMonth();
        $lastMonthPeriod = AccountingPeriod::create([
            'company_id' => $company->id,
            'year' => $lastMonth->year,
            'month' => $lastMonth->month,
            'start_date' => $lastMonth->copy()->startOfMonth(),
            'end_date' => $lastMonth->copy()->endOfMonth(),
            'status' => 'open',
        ]);

        $party = Party::factory()->create([
            'company_id' => $company->id,
            'type' => 'supplier',
        ]);

        $cashbox = Cashbox::create([
            'company_id' => $company->id,
            'code' => 'KASA-01',
            'name' => 'Ana Kasa',
            'is_active' => true,
            'opening_balance' => 50000.00,
        ]);

        $documentService = app(DocumentService::class);
        $paymentService = app(PaymentService::class);
        $allocationService = app(AllocationService::class);
        $periodService = app(PeriodService::class);

        // 2) Create Document dated in last month (while period is open)
        $document = $documentService->createDocument([
            'company_id' => $company->id,
            'type' => DocumentType::SUPPLIER_INVOICE,
            'party_id' => $party->id,
            'document_date' => $lastMonth->toDateString(),
            'total_amount' => 1000.00,
        ]);
        $this->assertEquals(DocumentStatus::PENDING, $document->status);

        // 3) Lock last month's period
        $periodService->lockPeriod(
            $company->id,
            $lastMonth->year,
            $lastMonth->month,
            'Month-end close'
        );

        // 4) Create AccountingPeriod for current month (OPEN)
        AccountingPeriod::firstOrCreate(
            [
                'company_id' => $company->id,
                'year' => now()->year,
                'month' => now()->month,
            ],
            [
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'status' => 'open',
            ]
        );

        // 5) Create Payment dated today (current month = OPEN period)
        $payment = $paymentService->createPayment([
            'company_id' => $company->id,
            'type' => PaymentType::CASH_OUT,
            'party_id' => $party->id,
            'cashbox_id' => $cashbox->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
        ]);

        $documentTotalBefore = $document->fresh()->total_amount;
        $documentDateBefore = $document->document_date->toDateString();

        // 6) Allocate payment to locked document (must succeed; only status update on document)
        $allocations = $allocationService->allocate($payment, [
            ['document_id' => $document->id, 'amount' => 500.00],
        ]);

        $this->assertCount(1, $allocations);
        $this->assertEquals(500.00, $allocations[0]->amount);
        $this->assertEquals('active', $allocations[0]->status);

        $document->refresh();
        $this->assertContains($document->status, [DocumentStatus::PARTIAL, DocumentStatus::SETTLED]);
        $this->assertEquals(500.00, $document->allocated_amount);

        // Financial fields must remain unchanged
        $this->assertEquals($documentTotalBefore, $document->total_amount);
        $this->assertEquals($documentDateBefore, $document->document_date->toDateString());
    }
}
