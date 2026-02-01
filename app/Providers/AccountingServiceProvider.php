<?php

namespace App\Providers;

use App\Domain\Accounting\Services\AllocationService;
use App\Domain\Accounting\Services\ChequeService;
use App\Domain\Accounting\Services\DocumentService;
use App\Domain\Accounting\Services\PartyService;
use App\Domain\Accounting\Services\PaymentService;
use App\Domain\Accounting\Services\PeriodService;
use App\Domain\Accounting\Services\ReportService;
use Illuminate\Support\ServiceProvider;

class AccountingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Core services - singleton for performance
        $this->app->singleton(PeriodService::class);
        $this->app->singleton(PartyService::class);
        $this->app->singleton(ReportService::class);
        
        // Transactional services
        $this->app->bind(DocumentService::class, function ($app) {
            return new DocumentService($app->make(PeriodService::class));
        });
        
        $this->app->bind(PaymentService::class, function ($app) {
            return new PaymentService($app->make(PeriodService::class));
        });
        
        $this->app->bind(AllocationService::class, function ($app) {
            return new AllocationService(
                $app->make(PeriodService::class),
                $app->make(DocumentService::class)
            );
        });
        
        $this->app->bind(ChequeService::class, function ($app) {
            return new ChequeService(
                $app->make(DocumentService::class),
                $app->make(PaymentService::class),
                $app->make(AllocationService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
