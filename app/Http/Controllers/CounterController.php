<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use Illuminate\Http\Request;

class CounterController extends Controller
{
    // List all counters
    public function index()
    {
        return response()->json([
            'success' => true,
            'counters' => Counter::with(['branch','service','user'])->get()
        ]);
    }

    // Create a new counter
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'branch_id'  => 'required|exists:branches,id',
            'service_id' => 'required|exists:services,id',
            'user_id'    => 'nullable|exists:users,id', // optional staff
        ]);

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
    public function show($id)
    {
        $counter = Counter::with(['branch','service','user'])->find($id);

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Counter not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'counter' => $counter
        ]);
    }

    // Update a counter
    public function update(Request $request, $id)
    {
        $counter = Counter::find($id);

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Counter not found'
            ], 404);
        }

        $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'branch_id'  => 'sometimes|required|exists:branches,id',
            'service_id' => 'sometimes|required|exists:services,id',
            'user_id'    => 'nullable|exists:users,id',
            'status'     => 'sometimes|required|in:active,inactive',
        ]);

        $counter->update($request->only(['name','branch_id','service_id','user_id','status']));

        return response()->json([
            'success' => true,
            'counter' => $counter
        ]);
    }

    // Delete a counter
    public function destroy($id)
    {
        $counter = Counter::find($id);

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Counter not found'
            ], 404);
        }

        $counter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Counter deleted successfully'
        ]);
    }
}
