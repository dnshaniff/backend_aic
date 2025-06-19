<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Users
            ['display_name' => 'View User Page', 'name' => 'pages.users', 'group_name' => 'Pages'],
            ['display_name' => 'View Users', 'name' => 'users.index', 'group_name' => 'Users'],
            ['display_name' => 'Create User', 'name' => 'users.store', 'group_name' => 'Users'],
            ['display_name' => 'View User Detail', 'name' => 'users.show', 'group_name' => 'Users'],
            ['display_name' => 'Update User', 'name' => 'users.update', 'group_name' => 'Users'],
            ['display_name' => 'Delete User', 'name' => 'users.destroy', 'group_name' => 'Users'],
            ['display_name' => 'Restore User', 'name' => 'users.restore', 'group_name' => 'Users'],
            ['display_name' => 'Force Delete User', 'name' => 'users.force', 'group_name' => 'Users'],

            // Permissions
            ['display_name' => 'View Permission Page', 'name' => 'pages.permissions', 'group_name' => 'Pages'],
            ['display_name' => 'View Permissions', 'name' => 'permissions.index', 'group_name' => 'Permissions'],
            ['display_name' => 'Create Permission', 'name' => 'permissions.store', 'group_name' => 'Permissions'],
            ['display_name' => 'View Permission Detail', 'name' => 'permissions.show', 'group_name' => 'Permissions'],
            ['display_name' => 'Update Permission', 'name' => 'permissions.update', 'group_name' => 'Permissions'],
            ['display_name' => 'Delete Permission', 'name' => 'permissions.destroy', 'group_name' => 'Permissions'],

            // Roles
            ['display_name' => 'View Role Page', 'name' => 'pages.roles', 'group_name' => 'Pages'],
            ['display_name' => 'View Roles', 'name' => 'roles.index', 'group_name' => 'Roles'],
            ['display_name' => 'Create Role', 'name' => 'roles.store', 'group_name' => 'Roles'],
            ['display_name' => 'View Role Detail', 'name' => 'roles.show', 'group_name' => 'Roles'],
            ['display_name' => 'Update Role', 'name' => 'roles.update', 'group_name' => 'Roles'],
            ['display_name' => 'Delete Role', 'name' => 'roles.destroy', 'group_name' => 'Roles'],

            // Employees
            ['display_name' => 'View Employee Page', 'name' => 'pages.employees', 'group_name' => 'Pages'],
            ['display_name' => 'View Employees', 'name' => 'employees.index', 'group_name' => 'Employees'],
            ['display_name' => 'Create Employee', 'name' => 'employees.store', 'group_name' => 'Employees'],
            ['display_name' => 'View Employee Detail', 'name' => 'employees.show', 'group_name' => 'Employees'],
            ['display_name' => 'Update Employee', 'name' => 'employees.update', 'group_name' => 'Employees'],
            ['display_name' => 'Delete Employee', 'name' => 'employees.destroy', 'group_name' => 'Employees'],
            ['display_name' => 'Restore Employee', 'name' => 'employees.restore', 'group_name' => 'Employees'],
            ['display_name' => 'Force Delete Employee', 'name' => 'employees.force', 'group_name' => 'Employees'],

            // Categories
            ['display_name' => 'View Category Page', 'name' => 'pages.categories', 'group_name' => 'Pages'],
            ['display_name' => 'View Categories', 'name' => 'categories.index', 'group_name' => 'Categories'],
            ['display_name' => 'Create Category', 'name' => 'categories.store', 'group_name' => 'Categories'],
            ['display_name' => 'View Category Detail', 'name' => 'categories.show', 'group_name' => 'Categories'],
            ['display_name' => 'Update Category', 'name' => 'categories.update', 'group_name' => 'Categories'],
            ['display_name' => 'Delete Category', 'name' => 'categories.destroy', 'group_name' => 'Categories'],
            ['display_name' => 'Restore Category', 'name' => 'categories.restore', 'group_name' => 'Categories'],
            ['display_name' => 'Force Delete Category', 'name' => 'categories.force', 'group_name' => 'Categories'],

            // Reimbursements
            ['display_name' => 'View Reimbursement Page', 'name' => 'pages.reimbursements', 'group_name' => 'Pages'],
            ['display_name' => 'View Reimbursements', 'name' => 'reimbursements.index', 'group_name' => 'Reimbursements'],
            ['display_name' => 'Create Reimbursement', 'name' => 'reimbursements.store', 'group_name' => 'Reimbursements'],
            ['display_name' => 'View Reimbursement Detail', 'name' => 'reimbursements.show', 'group_name' => 'Reimbursements'],
            ['display_name' => 'Update Reimbursement', 'name' => 'reimbursements.update', 'group_name' => 'Reimbursements'],
            ['display_name' => 'Delete Reimbursement', 'name' => 'reimbursements.destroy', 'group_name' => 'Reimbursements'],
            ['display_name' => 'Submit Reimbursement', 'name' => 'reimbursements.submit', 'group_name' => 'Reimbursements'],
            ['display_name' => 'Approve / Reject Reimbursement', 'name' => 'reimbursements.approval', 'group_name' => 'Reimbursements'],
            ['display_name' => 'Restore Reimbursement', 'name' => 'reimbursements.restore', 'group_name' => 'Reimbursements'],
            ['display_name' => 'Force Delete Reimbursement', 'name' => 'reimbursements.force', 'group_name' => 'Reimbursements'],

            // Dashboard
            ['display_name' => 'View Dashboard Page', 'name' => 'pages.dashboard', 'group_name' => 'Pages']
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['display_name' => $permission['display_name'], 'group_name' => $permission['group_name'], 'guard_name' => 'sanctum']
            );
        }
    }
}
