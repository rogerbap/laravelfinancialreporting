<?php
// database/seeders/DatabaseSeeder.php

// namespace Database\Seeders;

// use Illuminate\Database\Seeder;
// use App\Models\{User, Category, Transaction};
// use Spatie\Permission\Models\{Role, Permission};

// class DatabaseSeeder extends Seeder
// {
//     public function run(): void
//     {
//         // Create permissions
//         $permissions = [
//             'view transactions', 'create transactions', 'edit transactions', 
//             'delete transactions', 'approve transactions', 'import transactions', 
//             'export transactions', 'view reports', 'create reports', 'edit reports', 
//             'delete reports', 'publish reports', 'view categories', 'create categories', 
//             'edit categories', 'delete categories'
//         ];

//         foreach ($permissions as $permission) {
//             Permission::create(['name' => $permission]);
//         }

//         // Create roles
//         $adminRole = Role::create(['name' => 'admin']);
//         $managerRole = Role::create(['name' => 'manager']);
//         $userRole = Role::create(['name' => 'user']);

//         // Assign permissions to roles
//         $adminRole->givePermissionTo($permissions);
//         $managerRole->givePermissionTo([
//             'view transactions', 'create transactions', 'edit transactions',
//             'approve transactions', 'export transactions', 'view reports',
//             'create reports', 'edit reports', 'publish reports', 'view categories'
//         ]);
//         $userRole->givePermissionTo([
//             'view transactions', 'create transactions', 'view reports', 'view categories'
//         ]);

//         // Create users
//         $admin = User::factory()->create([
//             'name' => 'Admin User',
//             'email' => 'admin@financial-tool.com',
//         ]);
//         $admin->assignRole('admin');

//         $manager = User::factory()->create([
//             'name' => 'Manager User',
//             'email' => 'manager@financial-tool.com',
//         ]);
//         $manager->assignRole('manager');

//         $user = User::factory()->create([
//             'name' => 'Regular User',
//             'email' => 'user@financial-tool.com',
//         ]);
//         $user->assignRole('user');

//         // Create categories
//         $categories = [
//             ['name' => 'Office Supplies', 'code' => 'OFF', 'color' => '#3498db'],
//             ['name' => 'Marketing', 'code' => 'MKT', 'color' => '#e74c3c'],
//             ['name' => 'Travel', 'code' => 'TRV', 'color' => '#2ecc71'],
//             ['name' => 'Utilities', 'code' => 'UTL', 'color' => '#f39c12'],
//             ['name' => 'Revenue', 'code' => 'REV', 'color' => '#27ae60'],
//             ['name' => 'Equipment', 'code' => 'EQP', 'color' => '#8e44ad'],
//         ];

//         foreach ($categories as $category) {
//             Category::create(array_merge($category, [
//                 'description' => "Expenses and income related to {$category['name']}",
//                 'created_by' => $admin->id
//             ]));
//         }

//         // Create sample transactions if in local environment
//         if (app()->environment('local')) {
//             $this->call([
//                 TransactionSeeder::class,
//                 ReportSeeder::class,
//             ]);
//         }
//     }
// }
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Category, Transaction};
use Spatie\Permission\Models\{Role, Permission};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create or find admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@financial-tool.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );

        // Create categories only if they don't exist
        if (Category::count() == 0) {
            $categories = [
                ['name' => 'Office Supplies', 'code' => 'OFF', 'color' => '#3498db'],
                ['name' => 'Marketing', 'code' => 'MKT', 'color' => '#e74c3c'],
                ['name' => 'Travel', 'code' => 'TRV', 'color' => '#2ecc71'],
                ['name' => 'Utilities', 'code' => 'UTL', 'color' => '#f39c12'],
                ['name' => 'Revenue', 'code' => 'REV', 'color' => '#27ae60'],
                ['name' => 'Equipment', 'code' => 'EQP', 'color' => '#8e44ad'],
            ];

            foreach ($categories as $category) {
                Category::create(array_merge($category, [
                    'description' => "Expenses and income related to {$category['name']}",
                    'created_by' => $admin->id
                ]));
            }
        }

        // Create sample transactions only if none exist
        if (Transaction::count() == 0 && app()->environment('local')) {
            $this->call([TransactionSeeder::class]);
        }
    }
}