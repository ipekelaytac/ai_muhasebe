<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\PayrollPeriod;
use App\Models\PayrollItem;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
use App\Domain\Accounting\Services\PayrollDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollAccountingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'is_admin' => true,
        ]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function employee_creation_creates_party_automatically()
    {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'full_name' => 'Test Employee',
            'status' => 1,
        ]);

        $employee->refresh();
        
        $this->assertNotNull($employee->party_id, 'Employee should have party_id after creation');
        
        $party = Party::find($employee->party_id);
        $this->assertNotNull($party);
        $this->assertEquals('employee', $party->type);
        $this->assertEquals($employee->full_name, $party->name);
        $this->assertEquals($this->company->id, $party->company_id);
        $this->assertEquals($this->branch->id, $party->branch_id);
    }

    /** @test */
    public function payroll_item_creation_generates_accounting_document()
    {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 1,
        ]);
        
        // Ensure employee has party
        if (!$employee->party_id) {
            $party = Party::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'type' => 'employee',
                'name' => $employee->full_name,
            ]);
            $employee->party_id = $party->id;
            $employee->save();
        }

        EmployeeContract::factory()->create([
            'employee_id' => $employee->id,
            'monthly_net_salary' => 10000,
            'meal_allowance' => 500,
            'pay_day_1' => 5,
            'pay_day_2' => 20,
            'pay_amount_1' => 5000,
            'pay_amount_2' => 5500,
        ]);

        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'year' => 2026,
            'month' => 2,
        ]);

        $payrollItem = PayrollItem::factory()->create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'base_net_salary' => 10000,
            'meal_allowance' => 500,
            'overtime_total' => 0,
            'bonus_total' => 0,
            'deduction_total' => 0,
            'advances_deducted_total' => 0,
            'net_payable' => 10500,
        ]);

        // Create document for payroll item
        $service = app(PayrollDocumentService::class);
        $document = $service->createDocumentForPayrollItem($payrollItem);

        $payrollItem->refresh();

        $this->assertNotNull($payrollItem->document_id, 'PayrollItem should have document_id');
        $this->assertEquals($document->id, $payrollItem->document_id);
        
        $this->assertEquals('payroll_due', $document->type);
        $this->assertEquals('payable', $document->direction);
        $this->assertEquals($employee->party_id, $document->party_id);
        $this->assertEquals(10500, $document->total_amount);
        $this->assertEquals(PayrollItem::class, $document->source_type);
        $this->assertEquals($payrollItem->id, $document->source_id);
    }

    /** @test */
    public function accounting_payment_can_be_allocated_to_payroll_document()
    {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 1,
        ]);
        
        if (!$employee->party_id) {
            $party = Party::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'type' => 'employee',
                'name' => $employee->full_name,
            ]);
            $employee->party_id = $party->id;
            $employee->save();
        }

        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'year' => 2026,
            'month' => 2,
        ]);

        $payrollItem = PayrollItem::factory()->create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'net_payable' => 10500,
        ]);

        // Create document
        $service = app(PayrollDocumentService::class);
        $document = $service->createDocumentForPayrollItem($payrollItem);

        // Create payment
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'party_id' => $employee->party_id,
            'type' => 'cash_out',
            'direction' => 'out',
            'amount' => 5000,
            'payment_date' => now(),
        ]);

        // Allocate payment to document
        $allocation = PaymentAllocation::create([
            'payment_id' => $payment->id,
            'document_id' => $document->id,
            'allocated_amount' => 5000,
            'status' => 'active',
        ]);

        $payrollItem->refresh();
        
        $this->assertEquals(5000, $payrollItem->total_paid);
        $this->assertEquals(5500, $payrollItem->total_remaining);
    }

    /** @test */
    public function payroll_item_page_shows_accounting_payments_not_payroll_payments()
    {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 1,
        ]);
        
        if (!$employee->party_id) {
            $party = Party::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'type' => 'employee',
                'name' => $employee->full_name,
            ]);
            $employee->party_id = $party->id;
            $employee->save();
        }

        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $payrollItem = PayrollItem::factory()->create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'net_payable' => 10000,
        ]);

        $service = app(PayrollDocumentService::class);
        $document = $service->createDocumentForPayrollItem($payrollItem);

        $response = $this->get(route('admin.payroll.item', $payrollItem));

        $response->assertStatus(200);
        $response->assertSee('Muhasebede Ödeme Yap');
        $response->assertDontSee('Yeni Ödeme Ekle'); // Old PayrollPayment button
    }
}
