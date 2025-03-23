<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Class AuthController
 *
 * @package App\Http\Controllers\Api
 */
class AuthController extends BaseController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        Log::info('Login attempt', ['email' => $request->email]);

        // Validate the request
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Find user by email
        $user = User::where('email', $request->input('email'))->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            Log::warning('Login failed', ['email' => $request->email]);
            return responseFailed('These credentials do not match our records.', Response::HTTP_UNAUTHORIZED);
        }

        // Check if the user has a role assigned
        if (empty($user->role)) {
            Log::warning('User has no role', ['email' => $request->email]);
            return responseFailed('Access denied. No role assigned.', Response::HTTP_FORBIDDEN);
        }

        // Generate API token using Laravel Sanctum
        $user->token = $user->createToken('laravel-vue-admin')->plainTextToken;

        // Attach role & permissions to response
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role, // Directly using the role field
            'token' => $user->token
        ];

        Log::info('Login successful', ['user' => $userData]);

        return responseSuccess($userData);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        return responseSuccess();
    }

    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
