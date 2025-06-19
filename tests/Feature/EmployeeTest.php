<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create([
            'username' => 'adminuser',
            'password' => Hash::make('Password1'),
            'status' => 'active',
        ]);

        Permission::create(['display_name' => 'View Employees', 'name' => 'employees.index', 'group_name' => 'Employees', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'Create Employee', 'name' => 'employees.store', 'group_name' => 'Employees', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'View Employee Detail', 'name' => 'employees.show', 'group_name' => 'Employees', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'Update Employee', 'name' => 'employees.update', 'group_name' => 'Employees', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'Delete Employee', 'name' => 'employees.destroy', 'group_name' => 'Employees', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'Restore Employee', 'name' => 'employees.restore', 'group_name' => 'Employees', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'Force Delete Employee', 'name' => 'employees.force', 'group_name' => 'Employees', 'guard_name' => 'sanctum']);

        $user->givePermissionTo(Permission::all());

        $response = $this->postJson('/api/login', [
            'username' => 'adminuser',
            'password' => 'Password1',
        ]);

        $this->token = $response->json('token');
    }

    public function test_it_can_list_employees()
    {
        Employee::create([
            'nik' => 'EMP001',
            'email' => 'alpha@example.com',
            'full_name' => 'Alpha',
            'position' => 'Staff'
        ]);

        $response = $this->getJson('/api/employees', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()->assertJsonStructure(['data']);
    }

    public function test_it_can_store_an_employee()
    {
        $data = [
            'nik' => 'EMP001',
            'email' => 'john@example.com',
            'full_name' => 'John Doe',
            'position' => 'Manager',
        ];

        $response = $this->postJson('/api/employees', $data, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['full_name' => 'John Doe']);

        $this->assertDatabaseHas('employees', ['nik' => 'EMP001']);
    }

    public function test_it_can_show_an_employee()
    {
        $employee = Employee::create([
            'nik' => 'EMP002',
            'email' => 'jane@example.com',
            'full_name' => 'Jane Doe',
            'position' => 'Staff'
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}", [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['nik' => $employee->nik]);
    }

    public function test_it_can_update_an_employee()
    {
        $employee = Employee::create([
            'nik' => 'EMP003',
            'email' => 'jane@example.com',
            'full_name' => 'Jane Doe',
            'position' => 'Staff'
        ]);

        User::create([
            'employee_id' => $employee->id,
            'username' => strtolower($employee->nik),
            'password' => Hash::make('Password1'),
            'status' => 'active',
        ]);

        $data = [
            'nik' => 'EMPUPDATED',
            'email' => 'jane.updated@example.com',
            'full_name' => 'Jane Smith',
            'position' => 'Supervisor',
        ];

        $response = $this->putJson("/api/employees/{$employee->id}", $data, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['full_name' => 'Jane Smith']);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'email' => 'jane.updated@example.com',
            'full_name' => 'Jane Smith',
            'nik' => 'EMPUPDATED',
        ]);

        $this->assertDatabaseHas('users', [
            'employee_id' => $employee->id,
            'username' => 'empupdated',
        ]);
    }

    public function test_it_can_soft_delete_an_employee()
    {
        $employee = Employee::create([
            'nik' => 'EMP004',
            'email' => 'mark@example.com',
            'full_name' => 'Mark Test',
            'position' => 'Intern'
        ]);

        $response = $this->deleteJson("/api/employees/{$employee->id}", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Employee deleted successfully']);

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_it_can_restore_a_soft_deleted_employee()
    {
        $employee = Employee::create([
            'nik' => 'EMP005',
            'email' => 'soft.deleted@example.com',
            'full_name' => 'Soft Deleted',
            'position' => 'Staff'
        ]);
        $employee->delete();

        $response = $this->postJson("/api/employees/{$employee->id}/restore", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Employee restored successfully']);

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'deleted_at' => null]);
    }

    public function test_it_can_force_delete_a_soft_deleted_employee()
    {
        $employee = Employee::create([
            'nik' => 'EMP006',
            'email' => 'force.deleted@example.com',
            'full_name' => 'Force Delete',
            'position' => 'Staff'
        ]);
        $employee->delete();

        $response = $this->deleteJson("/api/employees/{$employee->id}/force", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Employee permanently deleted successfully']);

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }
}
