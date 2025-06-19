<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Employees
        $staffEmployee = Employee::create([
            'nik' => '2019148',
            'email' => 'dadang@example.com',
            'full_name' => 'Dadang Sunaryo',
            'position' => 'Staff',
        ]);

        $managerEmployee = Employee::create([
            'nik' => '2019149',
            'email' => 'cecep@example.com',
            'full_name' => 'Cecep Suparjo',
            'position' => 'Manager',
        ]);

        // Users
        $admin = User::create([
            'username' => 'administrator',
            'password' => Hash::make('Admin@123'),
            'status' => 'active',
        ]);
        $admin->assignRole(Role::where('name', 'administrator')->where('guard_name', 'sanctum')->first());

        $staffUser = User::create([
            'employee_id' => $staffEmployee->id,
            'username' => '2019148',
            'password' => Hash::make('User@123'),
            'status' => 'active',
        ]);
        $staffUser->assignRole(Role::where('name', 'staff')->where('guard_name', 'sanctum')->first());

        $managerUser = User::create([
            'employee_id' => $managerEmployee->id,
            'username' => '2019149',
            'password' => Hash::make('Manager@123'),
            'status' => 'active',
        ]);
        $managerUser->assignRole(Role::where('name', 'manager')->where('guard_name', 'sanctum')->first());
    }
}
