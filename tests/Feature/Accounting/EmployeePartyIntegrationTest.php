<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Employee;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Enums\PartyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePartyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    /** @test */
    public function creating_employee_creates_party_and_links_party_id()
    {
        $this->actingAs($this->user);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'full_name' => 'Test Employee',
            'phone' => '5551234567',
            'status' => true,
        ]);

        $this->assertNotNull($employee->party_id);
        
        $party = Party::find($employee->party_id);
        $this->assertNotNull($party);
        $this->assertEquals(PartyType::EMPLOYEE, $party->type);
        $this->assertEquals($employee->full_name, $party->name);
        $this->assertEquals($employee->phone, $party->phone);
        $this->assertEquals($employee->company_id, $party->company_id);
        $this->assertEquals($employee->branch_id, $party->branch_id);
        $this->assertEquals(Employee::class, $party->linkable_type);
        $this->assertEquals($employee->id, $party->linkable_id);
    }

    /** @test */
    public function updating_employee_updates_party()
    {
        $this->actingAs($this->user);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'full_name' => 'Test Employee',
            'phone' => '5551234567',
            'status' => true,
        ]);

        $partyId = $employee->party_id;

        $employee->update([
            'full_name' => 'Updated Name',
            'phone' => '5559998888',
            'status' => false,
        ]);

        $party = Party::find($partyId);
        $this->assertEquals('Updated Name', $party->name);
        $this->assertEquals('5559998888', $party->phone);
        $this->assertFalse($party->is_active);
    }

    /** @test */
    public function employee_party_appears_in_document_create_dropdown()
    {
        $this->actingAs($this->user);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'full_name' => 'Test Employee',
            'status' => true,
        ]);

        $response = $this->get(route('accounting.documents.create'));

        $response->assertStatus(200);
        $response->assertSee($employee->full_name, false);
        
        // Verify party exists
        $party = Party::find($employee->party_id);
        $this->assertNotNull($party);
        $response->assertSee($party->name, false);
    }

    /** @test */
    public function can_create_payroll_document_with_employee_party()
    {
        $this->actingAs($this->user);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'full_name' => 'Test Employee',
            'status' => true,
        ]);

        $party = Party::find($employee->party_id);

        $response = $this->post(route('accounting.documents.store'), [
            'type' => \App\Domain\Accounting\Enums\DocumentType::PAYROLL_DUE,
            'party_id' => $party->id,
            'document_date' => now()->toDateString(),
            'total_amount' => 5000.00,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('documents', [
            'party_id' => $party->id,
            'type' => \App\Domain\Accounting\Enums\DocumentType::PAYROLL_DUE,
            'total_amount' => 5000.00,
        ]);
    }

    /** @test */
    public function backfill_command_creates_parties_for_existing_employees()
    {
        // Create employee without party_id (simulating old data)
        $employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'full_name' => 'Old Employee',
            'status' => true,
        ]);

        // Manually set party_id to null (bypass observer)
        Employee::withoutEvents(function () use ($employee) {
            $employee->party_id = null;
            $employee->save();
        });

        $this->artisan('accounting:backfill-employee-parties', ['--force' => true])
            ->assertSuccessful();

        $employee->refresh();
        $this->assertNotNull($employee->party_id);

        $party = Party::find($employee->party_id);
        $this->assertNotNull($party);
        $this->assertEquals(PartyType::EMPLOYEE, $party->type);
        $this->assertEquals($employee->full_name, $party->name);
    }
}
