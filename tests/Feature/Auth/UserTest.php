<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    public function setUp(): void
    {
        parent::setUp();

        Role::create([
            'name' => 'administrator',
            'guard_name' => 'sanctum',
        ]);

        $user = User::factory()->create([
            'username' => 'adminuser',
            'password' => Hash::make('Password1'),
            'status' => 'active',
        ]);

        Permission::create(['display_name' => 'View Users', 'name' => 'users.index', 'group_name' => 'Users', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'Create User', 'name' => 'users.store', 'group_name' => 'Users', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'View User Detail', 'name' => 'users.show', 'group_name' => 'Users', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'Update User', 'name' => 'users.update', 'group_name' => 'Users', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'Delete User', 'name' => 'users.destroy', 'group_name' => 'Users', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'Restore User', 'name' => 'users.restore', 'group_name' => 'Users', 'guard_name' => 'sanctum']);
        Permission::create(['display_name' => 'Force Delete User', 'name' => 'users.force', 'group_name' => 'Users', 'guard_name' => 'sanctum']);

        $user->givePermissionTo(Permission::all());

        $response = $this->postJson('/api/login', [
            'username' => 'adminuser',
            'password' => 'Password1',
        ]);

        $this->token = $response->json('token');
    }

    public function test_index_users()
    {
        User::factory()->count(20)->create();

        $response = $this->getJson('/api/users?per_page=10', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'employee_id',
                    'username',
                    'status',
                    'created_at',
                ],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_store_user_with_employee_id_and_roles()
    {
        $employee = Employee::create([
            'nik' => 'EMP100',
            'email' => 'user@example.com',
            'full_name' => 'User With Role',
            'position' => 'Developer'
        ]);

        $data = [
            'employee_id' => $employee->id,
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'role' => 'administrator',
        ];

        $response = $this->postJson('/api/users', $data, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(201);
        $username = strtolower($employee->nik);

        $response->assertJsonFragment([
            'username' => $username,
            'status' => 'active',
            'role' => 'administrator',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => $username,
            'employee_id' => $employee->id
        ]);
    }

    public function test_show_user()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/users/{$user->id}", [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $user->id]);
    }

    public function test_update_user()
    {
        $employee = Employee::create([
            'nik' => 'EMP123',
            'email' => 'original@example.com',
            'full_name' => 'Original Employee',
            'position' => 'Analyst',
        ]);

        $user = User::create([
            'employee_id' => $employee->id,
            'username' => strtolower($employee->nik),
            'password' => Hash::make('Password1'),
            'status' => 'active',
        ]);

        Role::create(['name' => 'manager', 'guard_name' => 'sanctum']);

        $data = [
            'employee_id' => $employee->id,
            'status' => 'inactive',
            'role' => 'manager',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ];

        $response = $this->putJson("/api/users/{$user->id}", $data, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'inactive',
            'role' => 'manager',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'inactive',
            'employee_id' => $employee->id,
        ]);
    }

    public function test_delete_user()
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_restore_user()
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->postJson("/api/users/{$user->id}/restore", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_force_delete_user()
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->deleteJson("/api/users/{$user->id}/force", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
