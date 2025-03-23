<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Role;
// Assuming you have a Role model
use App\Models\Permission;
// Assuming you have a Permission model
use Illuminate\Support\Facades\DB;

/**
 * Class Acl
 *
 * @package App\Laravue
 */
final class Acl
{
    const ROLE_ADMIN = 'admin';
//const ROLE_MANAGER = 'manager';
  //  const ROLE_EDITOR = 'editor';
  //  const ROLE_USER = 'user';
 //   const ROLE_VISITOR = 'visitor';
    const ROLE_DONOR = 'donor';
    const ROLE_FOODBANK = 'foodbank';
    const ROLE_RECIPIENT = 'recipient';
    const PERMISSION_VIEW_MENU_PERMISSION = 'view menu permission';
    const PERMISSION_VIEW_MENU_ADMINISTRATOR = 'view menu administrator';
    const PERMISSION_VIEW_MENU_CHARTS = 'view menu charts';
    const PERMISSION_USER_MANAGE = 'manage user';
    const PERMISSION_ARTICLE_MANAGE = 'manage article';
    const PERMISSION_PERMISSION_MANAGE = 'manage permission';
// Add permissions for foodbank, donor, and recipient
    const PERMISSION_MANAGE_DONOR = 'manage donor';
    const PERMISSION_MANAGE_FOODBANK = 'manage foodbank';
    const PERMISSION_MANAGE_RECIPIENT = 'manage recipient';
/**
     * @param array $exclusives Exclude some permissions from the list
     * @return array
     */
    public static function permissions(array $exclusives = []): array
    {
        try {
            $class = new \ReflectionClass(__CLASS__);
            $constants = $class->getConstants();
            $permissions = Arr::where($constants, function ($value, $key) use ($exclusives) {

                return !in_array($value, $exclusives) && Str::startsWith($key, 'PERMISSION_');
            });
            return array_values($permissions);
        } catch (\ReflectionException $exception) {
            return [];
        }
    }

    public static function menuPermissions(): array
    {
        try {
            $class = new \ReflectionClass(__CLASS__);
            $constants = $class->getConstants();
            $permissions = Arr::where($constants, function ($value, $key) {

                return Str::startsWith($key, 'PERMISSION_VIEW_MENU_');
            });
            return array_values($permissions);
        } catch (\ReflectionException $exception) {
            return [];
        }
    }

    /**
     * @return array
     */
    public static function roles(): array
    {
        try {
            $class = new \ReflectionClass(__CLASS__);
            $constants = $class->getConstants();
            $roles =  Arr::where($constants, function ($value, $key) {

                return Str::startsWith($key, 'ROLE_');
            });
            return array_values($roles);
        } catch (\ReflectionException $exception) {
            return [];
        }
    }
}
