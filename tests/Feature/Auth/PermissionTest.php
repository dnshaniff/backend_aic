<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create([
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

    public function test_it_can_list_permissions()
    {
        Permission::create([
            'display_name' => 'Index',
            'name' => 'tests.index',
            'group_name' => 'Tests',
        ]);

        $response = $this->getJson('/api/permissions', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'display_name' => 'Index',
                'name' => 'tests.index',
                'group_name' => 'Tests',
            ]);
    }

    public function test_it_can_store_a_permission()
    {
        $data = [
            'display_name' => 'Index',
            'name' => 'tests.index',
            'group_name' => 'Tests',
        ];

        $response = $this->postJson('/api/permissions', $data, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'display_name' => 'Index',
                'name' => 'tests.index',
                'group_name' => 'Tests',
            ]);

        $this->assertDatabaseHas('permissions', [
            'display_name' => 'Index',
            'name' => 'tests.index',
            'group_name' => 'Tests',
        ]);
    }

    public function test_it_can_show_a_permission()
    {
        $permission = Permission::create([
            'display_name' => 'Show',
            'name' => 'tests.show',
            'group_name' => 'Tests',
        ]);

        $response = $this->getJson("/api/permissions/{$permission->id}", [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'tests.show']);
    }

    public function test_it_can_update_a_permission()
    {
        $permission = Permission::create([
            'display_name' => 'Index',
            'name' => 'tests.index',
            'group_name' => 'Tests',
        ]);

        $data = [
            'display_name' => 'Index',
            'name' => 'test.allData',
            'group_name' => 'Tests',
        ];

        $response = $this->putJson("/api/permissions/{$permission->id}", $data, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'display_name' => 'Index',
                'name' => 'test.allData',
                'group_name' => 'Tests',
            ]);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'display_name' => 'Index',
            'name' => 'test.allData',
            'group_name' => 'Tests',
        ]);
    }

    public function test_it_can_delete_a_permission()
    {
        $permission = Permission::create([
            'display_name' => 'Destroy',
            'name' => 'tests.destroy',
            'group_name' => 'Tests',
        ]);

        $response = $this->deleteJson("/api/permissions/{$permission->id}", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Permission deleted successfully']);

        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }
}
