<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryTest extends TestCase
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

    public function test_it_can_list_categories()
    {
        Category::create(['category_name' => 'Transport', 'limit_per_month' => 100]);

        $response = $this->getJson('/api/categories', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_it_can_store_a_category()
    {
        $data = [
            'category_name' => 'Food',
            'limit_per_month' => 150,
        ];

        $response = $this->postJson('/api/categories', $data, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['category_name' => 'Food']);

        $this->assertDatabaseHas('categories', $data);
    }

    public function test_it_can_show_a_category()
    {
        $category = Category::create(['category_name' => 'Accommodation', 'limit_per_month' => 200]);

        $response = $this->getJson("/api/categories/{$category->id}", [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['category_name' => 'Accommodation']);
    }

    public function test_it_can_update_a_category()
    {
        $category = Category::create(['category_name' => 'Training', 'limit_per_month' => 300]);

        $data = [
            'category_name' => 'Workshop',
            'limit_per_month' => 350,
        ];

        $response = $this->putJson("/api/categories/{$category->id}", $data, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['category_name' => 'Workshop']);

        $this->assertDatabaseHas('categories', $data);
    }

    public function test_it_can_soft_delete_a_category()
    {
        $category = Category::create(['category_name' => 'Internet', 'limit_per_month' => 120]);

        $response = $this->deleteJson("/api/categories/{$category->id}", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Category deleted successfully']);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_it_can_restore_a_soft_deleted_category()
    {
        $category = Category::create(['category_name' => 'Phone', 'limit_per_month' => 80]);
        $category->delete();

        $response = $this->postJson("/api/categories/{$category->id}/restore", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Category restored successfully']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    public function test_it_can_force_delete_a_category()
    {
        $category = Category::create(['category_name' => 'Parking', 'limit_per_month' => 50]);
        $category->delete();

        $response = $this->deleteJson("/api/categories/{$category->id}/force", [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Category permanently deleted successfully']);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
