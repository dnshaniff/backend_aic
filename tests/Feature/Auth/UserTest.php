<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
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

    public function test_store_user()
    {
        $response = $this->postJson('/api/users', [
            'username' => 'newuser',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'status' => 'active',
        ], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['username' => 'newuser']);
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
        $user = User::factory()->create([
            'username' => 'originaluser',
            'status' => 'active',
        ]);

        $response = $this->putJson("/api/users/{$user->id}", [
            'username' => 'updateduser',
            'status' => 'inactive',
        ], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['username' => 'updateduser']);
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
