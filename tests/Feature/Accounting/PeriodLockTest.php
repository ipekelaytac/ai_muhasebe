<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\DocumentService;
use App\Domain\Accounting\Services\PeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PeriodLockTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $branch;
    protected $period;
    protected $periodService;
    protected $documentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        
        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'year' => now()->year,
            'month' => now()->month,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => 'open',
        ]);

        $this->actingAs($this->user);
        $this->periodService = app(PeriodService::class);
        $this->documentService = app(DocumentService::class);
    }

    public function test_can_lock_period()
    {
        $lockedPeriod = $this->periodService->lockPeriod(
            $this->company->id,
            $this->period->year,
            $this->period->month,
            'Month end close'
        );

        $this->assertEquals('locked', $lockedPeriod->status);
        $this->assertEquals($this->user->id, $lockedPeriod->locked_by);
        $this->assertNotNull($lockedPeriod->locked_at);
        $this->assertEquals('Month end close', $lockedPeriod->lock_notes);
    }

    public function test_cannot_create_document_in_locked_period()
    {
        // Lock period
        $this->periodService->lockPeriod(
            $this->company->id,
            $this->period->year,
            $this->period->month
        );

        $party = Party::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $data = [
            'company_id' => $this->company->id,
            'type' => 'supplier_invoice',
            'party_id' => $party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('kilitli');

        $this->documentService->createDocument($data);
    }

    public function test_can_unlock_period()
    {
        // Lock first
        $this->periodService->lockPeriod(
            $this->company->id,
            $this->period->year,
            $this->period->month,
            'Test lock'
        );

        // Unlock
        $unlockedPeriod = $this->periodService->unlockPeriod(
            $this->company->id,
            $this->period->year,
            $this->period->month,
            'Test unlock'
        );

        $this->assertEquals('open', $unlockedPeriod->status);
        $this->assertNull($unlockedPeriod->locked_by);
        $this->assertNull($unlockedPeriod->locked_at);
    }
}
