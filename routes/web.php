<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeContractController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PayrollDeductionTypeController;
use App\Http\Controllers\Admin\AdvanceController;
use App\Http\Controllers\Admin\MealAllowanceController;
use App\Http\Controllers\Admin\SalaryCalculatorController;
use App\Http\Controllers\Admin\FinanceCategoryController;
use App\Http\Controllers\Admin\FinanceTransactionController;
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
    Route::post('admin/payroll/item/{item}/payment', [PayrollController::class, 'addPayment'])->name('admin.payroll.add-payment');
    Route::delete('admin/payroll/item/{item}/payment/{payment}', [PayrollController::class, 'deletePayment'])->name('admin.payroll.delete-payment');
    Route::post('admin/payroll/item/{item}/deduction', [PayrollController::class, 'addDeduction'])->name('admin.payroll.add-deduction');
    Route::delete('admin/payroll/item/{item}/deduction/{deduction}', [PayrollController::class, 'deleteDeduction'])->name('admin.payroll.delete-deduction');
    Route::post('admin/payroll/item/{item}/settle-advance', [PayrollController::class, 'settleAdvance'])->name('admin.payroll.settle-advance');
    Route::delete('admin/payroll/item/{item}/advance-settlement/{settlement}', [PayrollController::class, 'deleteAdvanceSettlement'])->name('admin.payroll.delete-advance-settlement');
    
    // Payroll Deduction Types
    Route::resource('admin/payroll/deduction-types', PayrollDeductionTypeController::class)->names([
        'index' => 'admin.payroll.deduction-types.index',
        'create' => 'admin.payroll.deduction-types.create',
        'store' => 'admin.payroll.deduction-types.store',
        'edit' => 'admin.payroll.deduction-types.edit',
        'update' => 'admin.payroll.deduction-types.update',
        'destroy' => 'admin.payroll.deduction-types.destroy',
    ]);
    
    // Advances
    Route::resource('admin/advances', AdvanceController::class)->names([
        'index' => 'admin.advances.index',
        'create' => 'admin.advances.create',
        'store' => 'admin.advances.store',
        'edit' => 'admin.advances.edit',
        'update' => 'admin.advances.update',
        'destroy' => 'admin.advances.destroy',
    ]);
    
    // Meal Allowance
    Route::get('admin/meal-allowance', [MealAllowanceController::class, 'index'])->name('admin.meal-allowance.index');
    Route::get('admin/meal-allowance/report', [MealAllowanceController::class, 'report'])->name('admin.meal-allowance.report');
    
    // Salary Calculator
    Route::get('admin/salary-calculator', [SalaryCalculatorController::class, 'index'])->name('admin.salary-calculator.index');
    Route::post('admin/salary-calculator/calculate', [SalaryCalculatorController::class, 'calculate'])->name('admin.salary-calculator.calculate');
    
    // Finance Categories
    Route::resource('admin/finance/categories', FinanceCategoryController::class)->names([
        'index' => 'admin.finance.categories.index',
        'create' => 'admin.finance.categories.create',
        'store' => 'admin.finance.categories.store',
        'edit' => 'admin.finance.categories.edit',
        'update' => 'admin.finance.categories.update',
        'destroy' => 'admin.finance.categories.destroy',
    ]);
    
    // Finance Transactions
    Route::resource('admin/finance/transactions', FinanceTransactionController::class)->names([
        'index' => 'admin.finance.transactions.index',
        'create' => 'admin.finance.transactions.create',
        'store' => 'admin.finance.transactions.store',
        'edit' => 'admin.finance.transactions.edit',
        'update' => 'admin.finance.transactions.update',
        'destroy' => 'admin.finance.transactions.destroy',
    ]);
    Route::get('admin/finance/reports', [FinanceTransactionController::class, 'reports'])->name('admin.finance.reports');
    Route::get('admin/finance/transactions/attachment/{attachment}', [FinanceTransactionController::class, 'showAttachment'])
        ->name('admin.finance.transactions.attachment.show');
    Route::delete('admin/finance/transactions/attachment/{attachment}', [FinanceTransactionController::class, 'destroyAttachment'])
        ->name('admin.finance.transactions.attachment.destroy');
    
    // Reports
    Route::get('admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
});

Route::get('/', function () {
    return redirect('/login');
});
