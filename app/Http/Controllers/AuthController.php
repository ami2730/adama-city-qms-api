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
        $request->validate([
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:6|confirmed'
        ]);

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'role'=>'customer'
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
            'success'=>true,
            'token'=>$user->createToken('auth')->plainTextToken,
            'user'=>$user
        ]);
    }
     // ✅ SUPER ADMIN + ADMIN CREATE USERS
    public function createUser(Request $request)
    {
        $auth = $request->user();

        if(!in_array($auth->role,['super_admin','admin'])){
            abort(403);
        }

        $request->validate([
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:6',
            'role'=>'required|in:admin,staff',
            'branch_id'=>'required|exists:branches,id'
        ]);

        if($auth->role === 'admin'){
            if($request->role !== 'staff' || $request->branch_id != $auth->branch_id){
                abort(403);
            }
        }

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'role'=>$request->role,
            'branch_id'=>$request->branch_id
        ]);

        return response()->json(['success'=>true,'user'=>$user],201);
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
     * Update own profile
     * PUT /api/me
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
        ]);

        $data = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user'    => $user,
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

        // 🔒 Admin only
        if ($authUser->role !== ['admin','super_admin']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
         if($auth->role === 'admin'){
            if($user->branch_id !== $auth->branch_id || $user->role !== 'staff'){
                abort(403);
            }
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|min:6|confirmed',
            'role'     => 'sometimes|in:admin,staff,customer',
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

        // 🔒 Admin only
        if ($authUser->role !== ['admin','super_admin']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
       if($auth->role === 'admin'){
            if($user->branch_id !== $auth->branch_id || $user->role !== 'staff'){
                abort(403);
            }
        }
        // Prevent admin deleting self
        if ($authUser->id == $id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account',
            ], 400);
        }
   
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Delete tokens first
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
  
        // 🔒 Only admin can access
        if ($authUser->role !== ["admin","super_admin"]) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
         $users = User::when($authUser->role === 'admin', function ($q) use ($authUser) {
            $q->where('branch_id',$authUser->branch_id);
        })->get();

        $users = User::select('id', 'name', 'email', 'role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

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
