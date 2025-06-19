<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'sanctum']);
        $manager->givePermissionTo([
            'pages.dashboard',
            'pages.reimbursements',
            'reimbursements.index',
            'reimbursements.show',
            'reimbursements.approval',
        ]);

        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'sanctum']);
        $staff->givePermissionTo([
            'pages.dashboard',
            'pages.reimbursements',
            'reimbursements.index',
            'reimbursements.show',
            'reimbursements.store',
            'reimbursements.update',
            'reimbursements.destroy',
        ]);
    }
}
