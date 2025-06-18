<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_successful_with_valid_credentials()
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
    }

    public function test_login_fails_with_wrong_password()
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_with_inactive_user()
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'status' => 'inactive'
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => 'password'
        ]);

        $response->assertStatus(403);
    }
}
