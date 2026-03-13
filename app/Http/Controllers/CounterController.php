<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use Illuminate\Http\Request;

class CounterController extends Controller
{
    // List all counters
    public function index(Request $request)
    {
        $user = $request->user();

        $counters = Counter::with(['branch', 'service', 'user'])
            ->when($user && in_array($user->role, ['admin', 'staff']), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })->get();

        return response()->json([
            'success' => true,
            'counters' => $counters
        ]);
    }

    // Create a new counter
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'       => 'required|string|max:255',
            'branch_id'  => 'required|exists:branches,id',
            'service_id' => 'required|exists:services,id',
            'user_id'    => 'nullable|exists:users,id', // optional staff
        ]);

        if ($user->role === 'admin' && $request->branch_id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only create counters for your own branch'], 403);
        }

        $counter = Counter::create([
            'name'       => $request->name,
            'branch_id'  => $request->branch_id,
            'service_id' => $request->service_id,
            'user_id'    => $request->user_id,
        ]);

        return response()->json([
            'success' => true,
            'counter' => $counter
        ], 201);
    }

    // Show a single counter
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $counter = Counter::with(['branch', 'service', 'user'])->find($id);

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Counter not found'
            ], 404);
        }

        if ($user->role === 'admin' && $counter->branch_id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only view counters in your own branch'], 403);
        }

        return response()->json([
            'success' => true,
            'counter' => $counter
        ]);
    }

    // Update a counter
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $counter = Counter::find($id);

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Counter not found'
            ], 404);
        }

        if ($user->role === 'admin' && $counter->branch_id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only update counters in your own branch'], 403);
        }

        $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'branch_id'  => 'sometimes|required|exists:branches,id',
            'service_id' => 'sometimes|required|exists:services,id',
            'user_id'    => 'nullable|exists:users,id',
            'status'     => 'sometimes|required|in:active,inactive',
        ]);

        if ($user->role === 'admin' && $request->has('branch_id') && $request->branch_id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You cannot change the branch of this counter'], 403);
        }

        $counter->update($request->only(['name', 'branch_id', 'service_id', 'user_id', 'status']));

        return response()->json([
            'success' => true,
            'counter' => $counter
        ]);
    }

    // Delete a counter
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $counter = Counter::find($id);

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Counter not found'
            ], 404);
        }

        if ($user->role === 'admin' && $counter->branch_id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only delete counters in your own branch'], 403);
        }

        $counter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Counter deleted successfully'
        ]);
    }
}
