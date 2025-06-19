<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Transportasi', 'limit_per_month' => 5],
            ['category_name' => 'Kesehatan', 'limit_per_month' => 2],
            ['category_name' => 'Makan', 'limit_per_month' => 3],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}
