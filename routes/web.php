<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeContractController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PayrollDeductionTypeController;
use App\Http\Controllers\Admin\MealAllowanceController;
use App\Http\Controllers\Admin\SalaryCalculatorController;
use App\Http\Controllers\Admin\CostCalculatorController;
use App\Http\Controllers\Admin\FinanceCategoryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\ProfileController;

// Auth Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    
    // Branches
    Route::resource('admin/branches', BranchController::class)->names([
        'index' => 'admin.branches.index',
        'create' => 'admin.branches.create',
        'store' => 'admin.branches.store',
        'edit' => 'admin.branches.edit',
        'update' => 'admin.branches.update',
        'destroy' => 'admin.branches.destroy',
    ]);
    
    // Employees
    Route::resource('admin/employees', EmployeeController::class)->names([
        'index' => 'admin.employees.index',
        'create' => 'admin.employees.create',
        'store' => 'admin.employees.store',
        'edit' => 'admin.employees.edit',
        'update' => 'admin.employees.update',
    ]);
    Route::post('admin/employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])
        ->name('admin.employees.toggle-status');
    
    // Contracts
    Route::resource('admin/contracts', EmployeeContractController::class)->names([
        'index' => 'admin.contracts.index',
        'create' => 'admin.contracts.create',
        'store' => 'admin.contracts.store',
        'edit' => 'admin.contracts.edit',
        'update' => 'admin.contracts.update',
    ]);
    
    // Payroll
    Route::get('admin/payroll', [PayrollController::class, 'index'])->name('admin.payroll.index');
    Route::get('admin/payroll/create', [PayrollController::class, 'create'])->name('admin.payroll.create');
    Route::post('admin/payroll', [PayrollController::class, 'store'])->name('admin.payroll.store');
    Route::get('admin/payroll/{period}', [PayrollController::class, 'show'])->name('admin.payroll.show');
    Route::post('admin/payroll/{period}/generate', [PayrollController::class, 'generate'])->name('admin.payroll.generate');
    Route::get('admin/payroll/{period}/add-employee', [PayrollController::class, 'addEmployeeForm'])->name('admin.payroll.add-employee-form');
    Route::post('admin/payroll/{period}/add-employee', [PayrollController::class, 'addEmployee'])->name('admin.payroll.add-employee');
    Route::get('admin/payroll/item/{item}', [PayrollController::class, 'showItem'])->name('admin.payroll.item');
    Route::post('admin/payroll/item/{item}/deduction', [PayrollController::class, 'addDeduction'])->name('admin.payroll.add-deduction');
    Route::delete('admin/payroll/item/{item}/deduction/{deduction}', [PayrollController::class, 'deleteDeduction'])->name('admin.payroll.delete-deduction');
    // Legacy advance routes removed - advance tables dropped
    // TODO: Migrate advance functionality to use accounting documents
    // Route::post('admin/payroll/item/{item}/settle-advance', [PayrollController::class, 'settleAdvance'])->name('admin.payroll.settle-advance');
    // Route::delete('admin/payroll/item/{item}/advance-settlement/{settlement}', [PayrollController::class, 'deleteAdvanceSettlement'])->name('admin.payroll.delete-advance-settlement');
    Route::post('admin/payroll/item/{item}/payment', [PayrollController::class, 'addPayment'])->name('admin.payroll.add-payment');
    Route::delete('admin/payroll/item/{item}/payment/{payment}', [PayrollController::class, 'deletePayment'])->name('admin.payroll.delete-payment');
    Route::post('admin/payroll/item/{item}/debt-payment', [PayrollController::class, 'addDebtPayment'])->name('admin.payroll.add-debt-payment');
    Route::delete('admin/payroll/item/{item}/debt-payment/{debtPayment}', [PayrollController::class, 'deleteDebtPayment'])->name('admin.payroll.delete-debt-payment');
    
    // Payroll Deduction Types
    Route::resource('admin/payroll/deduction-types', PayrollDeductionTypeController::class)->names([
        'index' => 'admin.payroll.deduction-types.index',
        'create' => 'admin.payroll.deduction-types.create',
        'store' => 'admin.payroll.deduction-types.store',
        'edit' => 'admin.payroll.deduction-types.edit',
        'update' => 'admin.payroll.deduction-types.update',
        'destroy' => 'admin.payroll.deduction-types.destroy',
    ]);
    
    // Meal Allowance
    Route::get('admin/meal-allowance', [MealAllowanceController::class, 'index'])->name('admin.meal-allowance.index');
    Route::get('admin/meal-allowance/report', [MealAllowanceController::class, 'report'])->name('admin.meal-allowance.report');
    
    // Salary Calculator
    Route::get('admin/salary-calculator', [SalaryCalculatorController::class, 'index'])->name('admin.salary-calculator.index');
    Route::post('admin/salary-calculator/calculate', [SalaryCalculatorController::class, 'calculate'])->name('admin.salary-calculator.calculate');
    
    // Cost Calculator
    Route::get('admin/cost-calculator', [CostCalculatorController::class, 'index'])->name('admin.cost-calculator.index');
    Route::post('admin/cost-calculator/calculate', [CostCalculatorController::class, 'calculate'])->name('admin.cost-calculator.calculate');
    
    // Finance Categories
    Route::resource('admin/finance/categories', FinanceCategoryController::class)->names([
        'index' => 'admin.finance.categories.index',
        'create' => 'admin.finance.categories.create',
        'store' => 'admin.finance.categories.store',
        'edit' => 'admin.finance.categories.edit',
        'update' => 'admin.finance.categories.update',
        'destroy' => 'admin.finance.categories.destroy',
    ]);
    
    // Reports
    Route::get('admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    
    // Accounting Web UI Routes
    Route::prefix('accounting')->name('accounting.')->group(function () {
        // Parties (Cariler)
        Route::get('parties', [\App\Http\Controllers\Web\Accounting\PartyController::class, 'index'])->name('parties.index');
        Route::get('parties/create', [\App\Http\Controllers\Web\Accounting\PartyController::class, 'create'])->name('parties.create');
        Route::post('parties', [\App\Http\Controllers\Web\Accounting\PartyController::class, 'store'])->name('parties.store');
        Route::get('parties/{party}', [\App\Http\Controllers\Web\Accounting\PartyController::class, 'show'])->name('parties.show');
        Route::get('parties/{party}/edit', [\App\Http\Controllers\Web\Accounting\PartyController::class, 'edit'])->name('parties.edit');
        Route::put('parties/{party}', [\App\Http\Controllers\Web\Accounting\PartyController::class, 'update'])->name('parties.update');
        
        // Documents (Tahakkuklar)
        Route::get('documents', [\App\Http\Controllers\Web\Accounting\DocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/create', [\App\Http\Controllers\Web\Accounting\DocumentController::class, 'create'])->name('documents.create');
        Route::post('documents', [\App\Http\Controllers\Web\Accounting\DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}', [\App\Http\Controllers\Web\Accounting\DocumentController::class, 'show'])->name('documents.show');
        Route::get('documents/{document}/edit', [\App\Http\Controllers\Web\Accounting\DocumentController::class, 'edit'])->name('documents.edit');
        Route::put('documents/{document}', [\App\Http\Controllers\Web\Accounting\DocumentController::class, 'update'])->name('documents.update');
        Route::post('documents/{document}/reverse', [\App\Http\Controllers\Web\Accounting\DocumentController::class, 'reverse'])->name('documents.reverse');
        Route::post('documents/{document}/cancel', [\App\Http\Controllers\Web\Accounting\DocumentController::class, 'cancel'])->name('documents.cancel');
        
        // Payments (Ödeme/Tahsilat)
        Route::get('payments', [\App\Http\Controllers\Web\Accounting\PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/create', [\App\Http\Controllers\Web\Accounting\PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [\App\Http\Controllers\Web\Accounting\PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{payment}', [\App\Http\Controllers\Web\Accounting\PaymentController::class, 'show'])->name('payments.show');
        Route::get('payments/{payment}/edit', [\App\Http\Controllers\Web\Accounting\PaymentController::class, 'edit'])->name('payments.edit');
        Route::put('payments/{payment}', [\App\Http\Controllers\Web\Accounting\PaymentController::class, 'update'])->name('payments.update');
        Route::post('payments/{payment}/reverse', [\App\Http\Controllers\Web\Accounting\PaymentController::class, 'reverse'])->name('payments.reverse');
        Route::post('payments/{payment}/cancel', [\App\Http\Controllers\Web\Accounting\PaymentController::class, 'cancel'])->name('payments.cancel');
        
        // Allocations (Dağıtım)
        Route::get('payments/{payment}/allocate', [\App\Http\Controllers\Web\Accounting\AllocationController::class, 'create'])->name('allocations.create');
        Route::post('payments/{payment}/allocate', [\App\Http\Controllers\Web\Accounting\AllocationController::class, 'store'])->name('allocations.store');
        Route::post('allocations/{allocation}/cancel', [\App\Http\Controllers\Web\Accounting\AllocationController::class, 'cancel'])->name('allocations.cancel');
        
        // Cash & Banks (Kasa & Bankalar)
        Route::get('cash', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'index'])->name('cash.index');
        Route::get('cash/transfer', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'transferForm'])->name('cash.transfer');
        Route::post('cash/transfer', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'transfer'])->name('cash.transfer.store');
        
        // Cashbox Management
        Route::get('cash/cashbox/create', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'createCashbox'])->name('cash.cashbox.create');
        Route::post('cash/cashbox', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'storeCashbox'])->name('cash.cashbox.store');
        Route::get('cash/cashbox/{cashbox}/edit', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'editCashbox'])->name('cash.cashbox.edit');
        Route::put('cash/cashbox/{cashbox}', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'updateCashbox'])->name('cash.cashbox.update');
        
        // Bank Account Management
        Route::get('cash/bank/create', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'createBankAccount'])->name('cash.bank.create');
        Route::post('cash/bank', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'storeBankAccount'])->name('cash.bank.store');
        Route::get('cash/bank/{bankAccount}/edit', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'editBankAccount'])->name('cash.bank.edit');
        Route::put('cash/bank/{bankAccount}', [\App\Http\Controllers\Web\Accounting\CashBankController::class, 'updateBankAccount'])->name('cash.bank.update');
        
        // Cheques (Çek/Senet)
        Route::get('cheques', [\App\Http\Controllers\Web\Accounting\ChequeController::class, 'index'])->name('cheques.index');
        Route::get('cheques/create', [\App\Http\Controllers\Web\Accounting\ChequeController::class, 'create'])->name('cheques.create');
        Route::post('cheques', [\App\Http\Controllers\Web\Accounting\ChequeController::class, 'store'])->name('cheques.store');
        Route::get('cheques/{cheque}', [\App\Http\Controllers\Web\Accounting\ChequeController::class, 'show'])->name('cheques.show');
        Route::post('cheques/{cheque}/deposit', [\App\Http\Controllers\Web\Accounting\ChequeController::class, 'deposit'])->name('cheques.deposit');
        Route::post('cheques/{cheque}/collect', [\App\Http\Controllers\Web\Accounting\ChequeController::class, 'collect'])->name('cheques.collect');
        Route::post('cheques/{cheque}/bounce', [\App\Http\Controllers\Web\Accounting\ChequeController::class, 'bounce'])->name('cheques.bounce');
        
        // Reports (Raporlar)
        Route::get('reports', [\App\Http\Controllers\Web\Accounting\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/cash-bank-balance', [\App\Http\Controllers\Web\Accounting\ReportController::class, 'cashBankBalance'])->name('reports.cash-bank-balance');
        Route::get('reports/receivables-aging', [\App\Http\Controllers\Web\Accounting\ReportController::class, 'receivablesAging'])->name('reports.receivables-aging');
        Route::get('reports/payables-aging', [\App\Http\Controllers\Web\Accounting\ReportController::class, 'payablesAging'])->name('reports.payables-aging');
        Route::get('reports/employee-dues-aging', [\App\Http\Controllers\Web\Accounting\ReportController::class, 'employeeDuesAging'])->name('reports.employee-dues-aging');
        Route::get('reports/cashflow-forecast', [\App\Http\Controllers\Web\Accounting\ReportController::class, 'cashflowForecast'])->name('reports.cashflow-forecast');
        Route::get('reports/party-statement/{party}', [\App\Http\Controllers\Web\Accounting\ReportController::class, 'partyStatement'])->name('reports.party-statement');
        Route::get('reports/monthly-pnl', [\App\Http\Controllers\Web\Accounting\ReportController::class, 'monthlyPnL'])->name('reports.monthly-pnl');
        
        // Periods (Dönem Kilit)
        Route::get('periods', [\App\Http\Controllers\Web\Accounting\PeriodController::class, 'index'])->name('periods.index');
        Route::post('periods/{period}/lock', [\App\Http\Controllers\Web\Accounting\PeriodController::class, 'lock'])->name('periods.lock');
        Route::post('periods/{period}/unlock', [\App\Http\Controllers\Web\Accounting\PeriodController::class, 'unlock'])->name('periods.unlock');
        
        // Employee Advances (Personel Avansları)
        Route::get('employees/{party}/advances', [\App\Http\Controllers\Web\Accounting\EmployeeAdvanceController::class, 'index'])->name('employees.advances.index');
        Route::get('employees/{party}/advances/create', [\App\Http\Controllers\Web\Accounting\EmployeeAdvanceController::class, 'create'])->name('employees.advances.create');
        Route::post('employees/{party}/advances', [\App\Http\Controllers\Web\Accounting\EmployeeAdvanceController::class, 'store'])->name('employees.advances.store');
        
        // Payroll Deductions (Maaş Kesintileri)
        Route::get('payroll/{salaryDocument}/deductions', [\App\Http\Controllers\Web\Accounting\PayrollDeductionController::class, 'show'])->name('payroll.deductions.show');
        Route::post('payroll/{salaryDocument}/deductions', [\App\Http\Controllers\Web\Accounting\PayrollDeductionController::class, 'store'])->name('payroll.deductions.store');
    });
    
    // Accounting API Routes
    Route::prefix('api/accounting')->name('api.accounting.')->group(function () {
        // Parties
        Route::apiResource('parties', \App\Http\Controllers\Accounting\PartyController::class);
        
        // Documents
        Route::apiResource('documents', \App\Http\Controllers\Accounting\DocumentController::class);
        Route::post('documents/{document}/reverse', [\App\Http\Controllers\Accounting\DocumentController::class, 'reverse'])
            ->name('documents.reverse');
        
        // Payments
        Route::apiResource('payments', \App\Http\Controllers\Accounting\PaymentController::class);
        
        // Payment Allocations
        Route::post('payments/{payment}/allocations', [\App\Http\Controllers\Accounting\PaymentAllocationController::class, 'store'])
            ->name('payments.allocations.store');
        Route::delete('payments/{payment}/allocations/{allocation}', [\App\Http\Controllers\Accounting\PaymentAllocationController::class, 'destroy'])
            ->name('payments.allocations.destroy');
        
        // Accounting Periods
        Route::get('periods', [\App\Http\Controllers\Accounting\AccountingPeriodController::class, 'index'])
            ->name('periods.index');
        Route::post('periods/{period}/lock', [\App\Http\Controllers\Accounting\AccountingPeriodController::class, 'lock'])
            ->name('periods.lock');
        Route::post('periods/{period}/unlock', [\App\Http\Controllers\Accounting\AccountingPeriodController::class, 'unlock'])
            ->name('periods.unlock');
        
        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('cash-bank-balance', [\App\Http\Controllers\Accounting\ReportController::class, 'cashBankBalance'])
                ->name('cash-bank-balance');
            Route::get('payables-aging', [\App\Http\Controllers\Accounting\ReportController::class, 'payablesAging'])
                ->name('payables-aging');
            Route::get('receivables-aging', [\App\Http\Controllers\Accounting\ReportController::class, 'receivablesAging'])
                ->name('receivables-aging');
            Route::get('employee-dues-aging', [\App\Http\Controllers\Accounting\ReportController::class, 'employeeDuesAging'])
                ->name('employee-dues-aging');
            Route::get('cashflow-forecast', [\App\Http\Controllers\Accounting\ReportController::class, 'cashflowForecast'])
                ->name('cashflow-forecast');
            Route::get('party-statement/{party}', [\App\Http\Controllers\Accounting\ReportController::class, 'partyStatement'])
                ->name('party-statement');
            Route::get('profit-loss', [\App\Http\Controllers\Accounting\ReportController::class, 'profitLoss'])
                ->name('profit-loss');
        });
    });
});

Route::get('/', function () {
    return redirect('/login');
});
