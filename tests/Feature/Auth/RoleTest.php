<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create([
            'username' => 'adminuser',
            'password' => Hash::make('Password1'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'adminuser',
            'password' => 'Password1',
        ]);

        $this->token = $response->json('token');
    }

    public function test_it_can_list_roles()
    {
        Role::create(['name' => 'admin']);

        $response = $this->getJson('/api/roles', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'admin']);
    }

    public function test_it_can_store_a_role()
    {
        $permission = Permission::create([
            'display_name' => 'Index',
            'name' => 'users.index',
            'group_name' => 'Users',
            'guard_name' => 'sanctum'
        ]);

        $data = [
            'name' => 'editor',
            'permissions' => ['users.index'],
        ];

        $response = $this->postJson('/api/roles', $data, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'editor'])
            ->assertJsonFragment(['permissions' => ['users.index']]);

        $this->assertDatabaseHas('roles', ['name' => 'editor']);
    }

    public function test_it_can_show_a_role()
    {
        $role = Role::create(['name' => 'viewer']);

        $response = $this->getJson("/api/roles/{$role->id}", [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'viewer']);
    }

    public function test_it_can_update_a_role()
    {
        $permission1 = Permission::create([
            'display_name' => 'Index',
            'name' => 'users.index',
            'group_name' => 'Users',
        ]);

        $permission2 = Permission::create([
            'display_name' => 'Create',
            'name' => 'users.create',
            'group_name' => 'Users',
        ]);

        $role = Role::create(['name' => 'contributor']);
        $role->syncPermissions(['users.index']);

        $data = [
            'name' => 'contributor-updated',
            'permissions' => ['users.index', 'users.create'],
        ];

        $response = $this->putJson("/api/roles/{$role->id}", $data, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'contributor-updated'])
            ->assertJsonFragment(['permissions' => ['users.index', 'users.create']]);

        $this->assertDatabaseHas('roles', ['name' => 'contributor-updated']);
    }

    public function test_it_can_delete_a_role()
    {
        $role = Role::create(['name' => 'deleteme']);

        $response = $this->deleteJson("/api/roles/{$role->id}", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Role deleted successfully']);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
