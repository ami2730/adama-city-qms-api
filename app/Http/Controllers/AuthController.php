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

        return response()->json(['success'=>true,'user'=>$user],201);
    }

    /**
     * Login (Admin / Staff / Customer)
     */
       public function login(Request $request)
    {
        $request->validate([
            'email'=>'required|email',
            'password'=>'required'
        ]);

        $user = User::where('email',$request->email)->first();

        if(!$user || !Hash::check($request->password,$user->password)){
            return response()->json(['success'=>false,'message'=>'Invalid credentials'],401);
        }

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
        /**
     * Update user (Admin only)
     * PUT /api/users/{id}
     */
    public function updateUser(Request $request, $id)
    {
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

        // Update password only if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user'    => $user,
        ], 200);
    }
    /**
     * Delete user (Admin only)
     * DELETE /api/users/{id}
     */
    public function deleteUser(Request $request, $id)
    {
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
        ], 200);
    }

        /**
     * List all users (Admin only)
     * GET /api/users
     */
    public function listUsers(Request $request)
    {
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
        ], 200);
    }

}
