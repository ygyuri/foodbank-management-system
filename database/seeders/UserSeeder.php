<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\Acl;
use App\Models\Role;
;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seed Admin user
        // Seed Admin user
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Get the admin role
        $adminRole = Role::findByName(Acl::ROLE_ADMIN);

        // Grant the admin role all permissions
        $adminRole->grantAdminPermissions(); // Call the method to assign all permissions

        // Assign the admin role to the admin user
        $adminUser->syncRoles($adminRole);

        // Seed Foodbanks
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "Foodbank User $i",
                'email' => "foodbank$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'foodbank',
            ]);
        }

        // Seed Donors
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => "Donor User $i",
                'email' => "donor$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'donor',
            ]);
        }

        // Seed Recipients
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => "Recipient User $i",
                'email' => "recipient$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'recipient',
            ]);
        }
    }
}