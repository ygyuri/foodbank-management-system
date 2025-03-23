<?php

namespace App\Models;

use Spatie\Permission\Models\Permission;

/**
 * Class Role
 *
 * @property Permission[] $permissions
 * @property string $name
 * @package App\Laravue\Models
 */
class Role extends \Spatie\Permission\Models\Role
{
    public $guard_name = 'api';
/**
     * Check whether current role is admin
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->name === Acl::ROLE_ADMIN;
    }

    /**
     * Assign all permissions to the admin role
     */
    public function grantAdminPermissions()
    {
        if ($this->isAdmin()) {
            $permissions = Acl::permissions();
// Get all permissions from the Acl model
            $this->givePermissionTo($permissions); // Assign all permissions to the role
        }
    }
}
