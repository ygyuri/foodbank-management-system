<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PermissionResource;
use App\Http\Resources\UserResource;
use App\Models\Acl;
// use App\Models\Log;
use App\Models\Log as LogModel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class UserController
 *
 * @package App\Http\Controllers\Api
 */
class UserController extends BaseController
{
   /**
     * Display a listing of the user resource with filtering and sorting.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        Log::info('Received Request Params:', $request->all());
        $params = $request->all();

        $query = User::query();

        // Filter by role
        if (!empty($params['role'])) {
            Log::info('Filtering by Role:', ['role' => $params['role']]);
            $query->whereHas('roles', function ($q) use ($params) {
                $q->where('name', $params['role']);
            });
        }

        // Filter by keyword (name or email)
        if (!empty($params['keyword'])) {
            Log::info('Filtering by Keyword:', ['keyword' => $params['keyword']]);
            $query->where(function ($q) use ($params) {
                $q->where('name', 'like', '%' . $params['keyword'] . '%')
                   ->orWhere('email', 'like', '%' . $params['keyword'] . '%');
            });
        }

        // Filter by status
        if (!empty($params['status'])) {
            Log::info('Filtering by Status:', ['status' => $params['status']]);
            $query->where('status', $params['status']);
        }

        // Filter by location
        if (!empty($params['location'])) {
            Log::info('Filtering by Location:', ['location' => $params['location']]);
            $query->where('location', 'like', '%' . $params['location'] . '%');
        }

        // Sorting logic
        if (!empty($params['sortBy']) && !empty($params['sortOrder'])) {
            $allowedSortFields = ['name', 'email', 'status', 'location'];
            $sortBy = in_array($params['sortBy'], $allowedSortFields) ? $params['sortBy'] : 'name';
            $sortOrder = in_array(strtolower($params['sortOrder']), ['asc', 'desc']) ? strtolower($params['sortOrder']) : 'asc';

            Log::info('Sorting Applied:', ['sortBy' => $sortBy, 'sortOrder' => $sortOrder]);
            $query->orderBy($sortBy, $sortOrder);
        }

        // Paginate results
        $perPage = $params['per_page'] ?? 10;
        Log::info('Pagination Applied:', ['per_page' => $perPage]);

        $data = $query->paginate($perPage);

        Log::info('Final Query Data:', ['total' => $data->total(), 'current_page' => $data->currentPage(), 'per_page' => $data->perPage()]);

        return UserResource::collection($data);
    }



    /**
     * Store a newly created user.
     *
     * @param Request $request
     * @return JsonResponse|UserResource
     */
    public function store(Request $request)
    {
        Log::info('Authenticated User', [
            'user_id' => Auth::id(),
            'user_roles' => Auth::user()->roles->pluck('name')->toArray(),
            'user_permissions' => Auth::user()->permissions->pluck('name')->toArray()
        ]);
        Log::info('Incoming User Store Request', ['request_data' => $request->all()]);


        Log::info('Incoming User Store Request', ['request_data' => $request->all()]);

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:6', 'confirmed'],
            'sex' => ['nullable', 'in:0,1'], // 0 => Male, 1 => Female
            'birthday' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
             'role' => ['required', 'string', Rule::in(Acl::roles())], // Validate role against predefined ACL roles
           // 'role' => ['required', 'string'], // Validate role
            'profile_picture' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'organization_name' => ['nullable', 'string'],
           'recipient_type' => ['nullable', Rule::in(['individual', 'organization'])],
            'donor_type' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            // Extract validated data
            $params = $request->only([
                'name', 'email', 'password', 'sex', 'birthday', 'description', 'phone',
                'profile_picture', 'location', 'address', 'organization_name',
                'recipient_type', 'donor_type', 'notes'
            ]);

            // Hash the password before storing
            $params['password'] = Hash::make($params['password']);

            // Create the user
            $user = User::create($params);

            $role = Role::where('name', $request->role)->first();
            if (!$role) {
                throw new \Exception("Invalid role selected.");
            }
            $user->syncRoles([$role]); // Ensure only one role is assigned

            if ($role->name === Acl::ROLE_ADMIN) {
                $user->givePermissionTo(Acl::permissions());
            }


            // Log user creation
            Log::info("User Created", [
                'user_id' => $user->id,
                'created_by' => Auth::id(),
                'email' => $user->email
            ]);

            DB::commit();
            return new UserResource($user);
        } catch (\Throwable $ex) {
            DB::rollBack();
            Log::error("User Creation Failed", ['error' => $ex->getMessage()]);

            return response()->json([
                'message' => 'User creation failed. Please try again.',
                'error' => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return UserResource|\Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return UserResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        if ($user === null) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $currentUser = Auth::user();
        if (
            !$currentUser->isAdmin()
            && $currentUser->id !== $user->id
            && !$currentUser->hasPermission(Acl::PERMISSION_USER_MANAGE)
        ) {
            return response()->json(['error' => 'Permission denied'], Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), array_merge($this->getValidationRules(false), [
            'role' => ['sometimes', 'string', Rule::in(Acl::roles())] // Optional role validation
        ]));
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        }

        try {
            DB::beginTransaction();

            $user->fill($request->only(['sex', 'birthday', 'description']));
            if ($request->has('role')) {
                $role = Role::where('name', $request->role)->first();
                if ($role) {
                    $user->syncRoles([$role]); // Update role securely
                } else {
                    throw new \Exception("Invalid role selected.");
                }
            }
            $user->save();

            DB::commit();
            return new UserResource($user);
        } catch (\Throwable $ex) {
            DB::rollBack();
            Log::error("User Update Failed", ['error' => $ex->getMessage()]);

            return response()->json([
                'message' => 'User update failed. Please try again.',
                'error' => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return UserResource|\Illuminate\Http\JsonResponse
     */
    public function updatePermissions(Request $request, User $user)
    {
        if (empty($user)) {
            return responseFailed('User not found', Response::HTTP_NOT_FOUND);
        }

        if ($user->isAdmin()) {
            return responseFailed('Admin can not be modified', Response::HTTP_BAD_REQUEST);
        }

        $permissionIds = $request->get('permissions', []);
        $rolePermissionIds = array_map(
            function ($permission) {
                return $permission['id'];
            },
            $user->getPermissionsViaRoles()->toArray()
        );

        $newPermissionIds = array_diff($permissionIds, $rolePermissionIds);
        $permissions = Permission::allowed()->whereIn('id', $newPermissionIds)->get();
        $user->syncPermissions($permissions);
        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     */
    public function destroy(User $user)
    {
        if ($user->isAdmin()) {
            return responseFailed('Ehhh! Can not delete admin user', Response::HTTP_FORBIDDEN);
        }

        try {
            $user->delete();
        } catch (\Exception $ex) {
            return responseFailed($ex->getMessage(), Response::HTTP_FORBIDDEN);
        }

        return responseSuccess();
    }

    /**
     * Get permissions from role
     *
     * @param User $user
     */
    public function permissions(User $user)
    {
        try {
            return responseSuccess([
                'user' => PermissionResource::collection($user->getDirectPermissions()),
                'role' => PermissionResource::collection($user->getPermissionsViaRoles()),
            ]);
        } catch (\Exception $ex) {
            return responseFailed($ex->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param bool $isNew
     * @return array
     */
    private function getValidationRules(bool $isNew = true): array
    {
        return [
            'name' => $isNew ? 'required|unique:users' : '',
            'email' => $isNew ? 'required|email|unique:users' : '',
            'role' => $isNew ? [
                'required',
                Rule::notIn([Acl::ROLE_ADMIN])
            ] : '',
            'sex' => [
                'required',
                Rule::in([0, 1])
            ],
            'birthday' => 'date_format:Y-m-d H:i:s',
            'description' => 'max:255'
        ];
    }

    /**
     * Approve a user by setting their status to 'approved'.
     */
    public function approveUser($id)
    {
        $user = User::findOrFail($id);

        // Ensure only an admin can approve users
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Update status
        $user->update(['status' => 'approved']);

        return response()->json(['message' => 'User approved successfully', 'user' => $user]);
    }

    /**
     * Reject a user by setting their status to 'rejected'.
     */
    public function rejectUser($id)
    {
        $user = User::findOrFail($id);

        // Ensure only an admin can reject users
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->update(['status' => 'rejected']);

        return response()->json(['message' => 'User rejected successfully', 'user' => $user]);
    }

    /**
     * Reset a user's status to 'pending'.
     */
    public function resetStatus($id)
    {
        $user = User::findOrFail($id);

        // Ensure only an admin can reset user status
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->update(['status' => 'pending']);

        return response()->json(['message' => 'User status reset to pending', 'user' => $user]);
    }

    public function getAllUsers(Request $request)
    {
        // Fetch query parameters
        $role = $request->query('role');
        $status = $request->query('status');

        // Query users with optional filters
        $query = User::query();

        if ($role) {
            $query->where('role', $role);
        }

        if ($status) {
            $query->where('status', $status);
        }

        // ✅ Return all filtered users
        return response()->json([
        'data' => $query->get()
        ]);
    }
}
