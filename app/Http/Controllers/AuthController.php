<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;

class AuthController extends Controller
{
    /**
     * Register a new customer (public endpoint)
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'customer',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully',
            'user'    => $user,
        ], 201);
    }

    /**
     * Login and issue Sanctum token
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success'    => true,
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => $user->only(['id', 'name', 'email', 'role', 'branch_id']),
        ]);
    }

    /**
     * Get the authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user'    => $request->user(),
        ]);
    }

    /**
     * Logout current device (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices',
        ]);
    }

    /**
     * Refresh token (token rotation)
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $abilities = $currentToken?->abilities ?? ['*'];

        $currentToken?->delete();

        $newToken = $user->createToken('refreshed-token', $abilities)->plainTextToken;

        return response()->json([
            'success'     => true,
            'access_token' => $newToken,
            'token_type'  => 'Bearer',
        ]);
    }

    // ────────────────────────────────────────────────
    // Admin / Super Admin Protected Methods
    // ────────────────────────────────────────────────

    /**
     * Create a new staff or admin user (super_admin or admin)
     */
    public function createUser(Request $request): JsonResponse
    {
        $this->authorizeAdminOrSuperAdmin();

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:8',
            'role'      => 'required|in:admin,staff',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $authUser = $request->user();

        // Branch admins can only create staff in their own branch
        if ($authUser->role === 'admin') {
            if ($validated['role'] !== 'staff' || $validated['branch_id'] !== $authUser->branch_id) {
                abort(403, 'Admins can only create staff users in their own branch.');
            }
        }

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role'      => $validated['role'],
            'branch_id' => $validated['branch_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user'    => $user,
        ], 201);
    }

    /**
     * Update an existing user (admin/super_admin)
     */
    public function updateUser(Request $request, string $id): JsonResponse
    {
        $this->authorizeAdminOrSuperAdmin();

        $user = User::findOrFail($id);

        $authUser = $request->user();

        // Branch admin restriction
        if ($authUser->role === 'admin') {
            if ($user->branch_id !== $authUser->branch_id || ! in_array($user->role, ['staff'])) {
                abort(403, 'You can only manage staff users in your branch.');
            }
        }

        $validated = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|min:8|confirmed',
            'role'     => 'sometimes|required|in:admin,staff,customer',
        ]);

        $data = $request->only(['name', 'email', 'role']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user'    => $user,
        ]);
    }

    /**
     * Delete a user (admin/super_admin)
     */
    public function deleteUser(Request $request, string $id): JsonResponse
    {
        $this->authorizeAdminOrSuperAdmin();

        $user = User::findOrFail($id);

        $authUser = $request->user();

        if ($authUser->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Cannot delete your own account'], 400);
        }

        if ($authUser->role === 'admin') {
            if ($user->branch_id !== $authUser->branch_id || $user->role !== 'staff') {
                abort(403, 'You can only delete staff users in your branch.');
            }
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * List users (filtered by branch for branch admins)
     */
    public function listUsers(Request $request): JsonResponse
    {
        $this->authorizeAdminOrSuperAdmin();

        $authUser = $request->user();

        $query = User::query()
            ->select('id', 'name', 'email', 'role', 'branch_id', 'created_at')
            ->orderBy('created_at', 'desc');

        if ($authUser->role === 'admin') {
            $query->where('branch_id', $authUser->branch_id)
                  ->whereIn('role', ['staff']);
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'count'   => $users->count(),
            'users'   => $users,
        ]);
    }

    // ────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────

    protected function authorizeAdminOrSuperAdmin(): void
    {
        $role = auth()->user()?->role;

        if (! in_array($role, ['admin', 'super_admin'])) {
            abort(403, 'Unauthorized action.');
        }
    }
}
