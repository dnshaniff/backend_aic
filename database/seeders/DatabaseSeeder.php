<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(15)->create();

        User::factory()->create([
            'username' => '2019148',
            'password' => Hash::make('Netmaster2022'),
            'status' => 'active'
        ]);

        User::factory()->create([
            'username' => 'administrator',
            'password' => Hash::make('P@ssw0rd'),
            'status' => 'active'
        ]);
    }
}
