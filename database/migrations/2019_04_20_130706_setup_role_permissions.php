<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Acl;

class SetupRolePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (Acl::roles() as $role) {
            Role::findOrCreate($role);
        }

        $adminRole = Role::findByName(Acl::ROLE_ADMIN);
       
      
      
       
        $donorRole = Role::findByName(Acl::ROLE_DONOR);
        $foodbankRole = Role::findByName(Acl::ROLE_FOODBANK);
        $recipientRole = Role::findByName(Acl::ROLE_RECIPIENT);

        foreach (Acl::permissions() as $permission) {
            Log::info("Creating permission: " . $permission);
            Permission::findOrCreate($permission, 'api');
        }

        // Setup basic permission
        $adminRole->givePermissionTo(Acl::permissions());
       
     
        $donorRole->givePermissionTo([ Acl::PERMISSION_VIEW_MENU_PERMISSION,]);
        $foodbankRole->givePermissionTo([ Acl::PERMISSION_VIEW_MENU_PERMISSION,]);
        $recipientRole->givePermissionTo([ Acl::PERMISSION_VIEW_MENU_PERMISSION,]);
        //dd(Acl::permissions());


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('editor');
            });
        }

        /** @var \App\User[] $users */
        $users = \App\Models\User::all();
        foreach ($users as $user) {
            $roles = array_reverse(Acl::roles());
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    $user->role = $role;
                    $user->save();
                }
            }
        }
    }
}
