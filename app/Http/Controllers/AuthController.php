<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register (Customer only)
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
                'role'     => 'nullable|in:admin,staff,customer',
            ]);
        } catch (ValidationException $e) {
            if (isset($e->errors()['email'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already registered',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role ?? 'customer',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user'    => $user,
        ], 201);
    }

    /**
     * Login (Admin / Staff / Customer)
     */
    public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    // 🔴 WRONG PASSWORD → 401
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password',
        ], 401);
    }

    // ✅ CORRECT PASSWORD
    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'user'    => $user,
        'access_token' => $token,
    ], 200);
}
    /**
     * Authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user'    => $request->user(),
        ]);
    }

    /**
     * Logout current device
     */
    public function logout(Request $request)
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Logout all devices
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices',
        ]);
    }

    /**
     * Refresh token (rotation)
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $abilities = $currentToken?->abilities ?? [];

        if ($currentToken) {
            $currentToken->delete();
        }

        $newToken = $user->createToken(
            'refreshed-token',
            $abilities
        )->plainTextToken;

        return response()->json([
            'success'      => true,
            'access_token'=> $newToken,
            'token_type'  => 'Bearer',
        ]);
    }
}
