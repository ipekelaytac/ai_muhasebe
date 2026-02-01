<?php

use App\Http\Controllers\Api\Accounting\AllocationController;
use App\Http\Controllers\Api\Accounting\ChequeController;
use App\Http\Controllers\Api\Accounting\DocumentController;
use App\Http\Controllers\Api\Accounting\PartyController;
use App\Http\Controllers\Api\Accounting\PaymentController;
use App\Http\Controllers\Api\Accounting\PeriodController;
use App\Http\Controllers\Api\Accounting\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Accounting API Routes
|--------------------------------------------------------------------------
|
| These routes handle the core accounting functionality:
| - Parties (cari hesaplar)
| - Documents (tahakkuklar)
| - Payments (ödemeler)
| - Allocations (kapamalar)
| - Cheques (çekler)
| - Periods (dönemler)
| - Reports (raporlar)
|
*/

Route::prefix('accounting')->middleware(['auth:sanctum'])->group(function () {
    
    // Parties (Cari Hesaplar)
    Route::prefix('parties')->group(function () {
        Route::get('/', [PartyController::class, 'index']);
        Route::post('/', [PartyController::class, 'store']);
        Route::get('/{id}', [PartyController::class, 'show']);
        Route::put('/{id}', [PartyController::class, 'update']);
        Route::delete('/{id}', [PartyController::class, 'destroy']);
    });
    
    // Documents (Belgeler / Tahakkuklar)
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('/{id}', [DocumentController::class, 'show']);
        Route::put('/{id}', [DocumentController::class, 'update']);
        Route::post('/{id}/cancel', [DocumentController::class, 'cancel']);
        Route::post('/{id}/reverse', [DocumentController::class, 'reverse']);
    });
    
    // Payments (Ödemeler)
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::put('/{id}', [PaymentController::class, 'update']);
        Route::post('/{id}/cancel', [PaymentController::class, 'cancel']);
        Route::post('/{id}/reverse', [PaymentController::class, 'reverse']);
    });
    
    // Allocations (Dağıtımlar / Kapamalar)
    Route::prefix('allocations')->group(function () {
        Route::post('/payment/{paymentId}', [AllocationController::class, 'allocate']);
        Route::post('/payment/{paymentId}/auto', [AllocationController::class, 'autoAllocate']);
        Route::get('/payment/{paymentId}/suggestions', [AllocationController::class, 'suggestions']);
        Route::post('/payment/{paymentId}/overpayment', [AllocationController::class, 'handleOverpayment']);
        Route::post('/{allocationId}/cancel', [AllocationController::class, 'cancel']);
    });
    
    // Cheques (Çekler)
    Route::prefix('cheques')->group(function () {
        Route::get('/', [ChequeController::class, 'index']);
        Route::post('/receive', [ChequeController::class, 'receive']);
        Route::post('/issue', [ChequeController::class, 'issue']);
        Route::get('/{id}', [ChequeController::class, 'show']);
        Route::post('/{id}/deposit', [ChequeController::class, 'deposit']);
        Route::post('/{id}/collect', [ChequeController::class, 'collect']);
        Route::post('/{id}/bounce', [ChequeController::class, 'bounce']);
        Route::post('/{id}/endorse', [ChequeController::class, 'endorse']);
        Route::post('/{id}/pay', [ChequeController::class, 'pay']);
        Route::post('/{id}/cancel', [ChequeController::class, 'cancel']);
    });
    
    // Periods (Dönemler)
    Route::prefix('periods')->group(function () {
        Route::get('/', [PeriodController::class, 'index']);
        Route::get('/open', [PeriodController::class, 'open']);
        Route::post('/lock', [PeriodController::class, 'lock']);
        Route::post('/unlock', [PeriodController::class, 'unlock']);
        Route::post('/close', [PeriodController::class, 'close']);
    });
    
    // Reports (Raporlar)
    Route::prefix('reports')->group(function () {
        Route::get('/cash-bank-balance', [ReportController::class, 'cashBankBalance']);
        Route::get('/payables-aging', [ReportController::class, 'payablesAging']);
        Route::get('/receivables-aging', [ReportController::class, 'receivablesAging']);
        Route::get('/employee-dues-aging', [ReportController::class, 'employeeDuesAging']);
        Route::get('/cashflow-forecast', [ReportController::class, 'cashflowForecast']);
        Route::get('/party-statement/{partyId}', [ReportController::class, 'partyStatement']);
        Route::get('/monthly-pnl', [ReportController::class, 'monthlyPnL']);
        Route::get('/top-suppliers', [ReportController::class, 'topSuppliers']);
        Route::get('/top-customers', [ReportController::class, 'topCustomers']);
    });
});
