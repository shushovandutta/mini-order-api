<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\LoginRequest;
use App\Http\Requests\Api\v1\RegisterRequest;
use App\Http\Resources\Api\v1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * User Registration API
     */
    public function register(RegisterRequest $request)
    {
        // Data is already validated because of Form Request
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Creation of Sanctum Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ], Response::HTTP_CREATED); // 201 Status Code
    }

    /**
     * User Login API
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // Check Email and Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid login credentials'
            ], Response::HTTP_UNAUTHORIZED); // 401 Status Code
        }

        // Deleted old token if found (Security best Practice)
        $user->tokens()->delete();
        
        // Generate new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ], Response::HTTP_OK); // 200 Status Code
    }

    /**
     * User Logout API
     */
    public function logout(Request $request)
    {
        // Delete the current token from the database
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ], Response::HTTP_OK);
    }
}