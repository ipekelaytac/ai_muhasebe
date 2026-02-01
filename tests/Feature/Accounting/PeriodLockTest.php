<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\AccountingPeriod;
use App\Models\Document;
use App\Services\CreateObligationService;
use App\Services\LockPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PeriodLockTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $branch;
    protected $period;
    protected $lockPeriodService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        
        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'year' => now()->year,
            'month' => now()->month,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => 'open',
        ]);

        $this->actingAs($this->user);
        $this->lockPeriodService = app(LockPeriodService::class);
    }

    public function test_can_lock_period()
    {
        $lockedPeriod = $this->lockPeriodService->lock($this->period, 'Month end close');

        $this->assertEquals('locked', $lockedPeriod->status);
        $this->assertEquals($this->user->id, $lockedPeriod->locked_by);
        $this->assertNotNull($lockedPeriod->locked_at);
        $this->assertEquals('Month end close', $lockedPeriod->lock_notes);
    }

    public function test_cannot_create_document_in_locked_period()
    {
        // Lock period
        $this->lockPeriodService->lock($this->period);

        $party = \App\Models\Party::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $createObligationService = app(CreateObligationService::class);

        $data = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type' => 'supplier_invoice',
            'direction' => 'payable',
            'party_id' => $party->id,
            'document_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot create document in locked period');

        $createObligationService->create($data);
    }

    public function test_can_unlock_period()
    {
        // Lock first
        $this->lockPeriodService->lock($this->period, 'Test lock');

        // Unlock
        $unlockedPeriod = $this->lockPeriodService->unlock($this->period, 'Test unlock');

        $this->assertEquals('open', $unlockedPeriod->status);
        $this->assertNull($unlockedPeriod->locked_by);
        $this->assertNull($unlockedPeriod->locked_at);
    }
}
