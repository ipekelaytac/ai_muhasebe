<?php

namespace Tests\Feature\Accounting;

use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Services\PeriodService;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected PeriodService $periodService;
    protected Company $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->periodService = app(PeriodService::class);
        $this->company = Company::create(['name' => 'Test Company']);
    }
    
    public function test_can_get_or_create_period(): void
    {
        $date = now();
        $period = $this->periodService->getOrCreatePeriod($this->company->id, $date);
        
        $this->assertNotNull($period->id);
        $this->assertEquals($date->year, $period->year);
        $this->assertEquals($date->month, $period->month);
        $this->assertEquals('open', $period->status);
    }
    
    public function test_existing_period_is_returned(): void
    {
        $date = now();
        $period1 = $this->periodService->getOrCreatePeriod($this->company->id, $date);
        $period2 = $this->periodService->getOrCreatePeriod($this->company->id, $date);
        
        $this->assertEquals($period1->id, $period2->id);
    }
    
    public function test_can_lock_period(): void
    {
        $date = now();
        $this->periodService->getOrCreatePeriod($this->company->id, $date);
        
        $locked = $this->periodService->lockPeriod($this->company->id, $date->year, $date->month, 'Month end close');
        
        $this->assertEquals('locked', $locked->status);
        $this->assertNotNull($locked->locked_at);
        $this->assertEquals('Month end close', $locked->lock_notes);
    }
    
    public function test_cannot_lock_already_locked_period(): void
    {
        $date = now();
        $this->periodService->getOrCreatePeriod($this->company->id, $date);
        $this->periodService->lockPeriod($this->company->id, $date->year, $date->month);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('zaten kilitli');
        
        $this->periodService->lockPeriod($this->company->id, $date->year, $date->month);
    }
    
    public function test_can_unlock_locked_period(): void
    {
        $date = now();
        $this->periodService->getOrCreatePeriod($this->company->id, $date);
        $this->periodService->lockPeriod($this->company->id, $date->year, $date->month);
        
        $unlocked = $this->periodService->unlockPeriod($this->company->id, $date->year, $date->month, 'Need to make correction');
        
        $this->assertEquals('open', $unlocked->status);
        $this->assertNull($unlocked->locked_at);
    }
    
    public function test_cannot_unlock_closed_period(): void
    {
        $date = now();
        $this->periodService->getOrCreatePeriod($this->company->id, $date);
        $this->periodService->lockPeriod($this->company->id, $date->year, $date->month);
        $this->periodService->closePeriod($this->company->id, $date->year, $date->month);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kapatılmış dönem');
        
        $this->periodService->unlockPeriod($this->company->id, $date->year, $date->month);
    }
    
    public function test_is_period_open_returns_true_for_open_period(): void
    {
        $date = now();
        $this->periodService->getOrCreatePeriod($this->company->id, $date);
        
        $this->assertTrue($this->periodService->isPeriodOpen($this->company->id, $date->year, $date->month));
    }
    
    public function test_is_period_open_returns_false_for_locked_period(): void
    {
        $date = now();
        $this->periodService->getOrCreatePeriod($this->company->id, $date);
        $this->periodService->lockPeriod($this->company->id, $date->year, $date->month);
        
        $this->assertFalse($this->periodService->isPeriodOpen($this->company->id, $date->year, $date->month));
    }
    
    public function test_is_period_open_returns_true_for_nonexistent_period(): void
    {
        // Period doesn't exist yet = open
        $this->assertTrue($this->periodService->isPeriodOpen($this->company->id, 2030, 1));
    }
    
    public function test_validate_period_open_throws_for_locked(): void
    {
        $date = now();
        $this->periodService->getOrCreatePeriod($this->company->id, $date);
        $this->periodService->lockPeriod($this->company->id, $date->year, $date->month);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('kilitli');
        
        $this->periodService->validatePeriodOpen($this->company->id, $date);
    }
}
