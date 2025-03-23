<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Http\Resources\RoleResource;
use Illuminate\Http\Response;

/**
 * Class RoleController
 *
 * @package App\Http\Controllers\Api
 */
class RoleController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $list = Role::query()->paginate($request->input('per_page', 10));
        return RoleResource::collection($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Role $role
     * @return RoleResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Role $role)
    {
        if (!$role || $role->isAdmin()) {
            return response()->json(['message' => 'Role not found'], Response::HTTP_NOT_FOUND);
        }

        $permissions = $request->input('permissions', []);

        // Sync permissions with the role
        $role->syncPermissions($permissions);

        return response()->json([
            'message' => 'Permissions updated successfully',
            'role' => new RoleResource($role)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Get permissions from role
     *
     * @param Role $role
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function permissions(Role $role)
    {
        return PermissionResource::collection($role->permissions);
    }
}
