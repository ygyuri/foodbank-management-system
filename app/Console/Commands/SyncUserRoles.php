<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SyncUserRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync existing user roles with Spatie roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            // Check if role exists in Spatie roles, if not create it
            $role = Role::firstOrCreate(['name' => $user->role]);

            // Sync user role
            if (!$user->hasRole($user->role)) {
                $user->syncRoles([$role->name]);
                $this->info("Assigned role '{$user->role}' to user {$user->email}");
            }
        }

        $this->info('User roles have been synchronized successfully.');
    }
}
