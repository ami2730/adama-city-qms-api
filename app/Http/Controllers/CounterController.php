<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Counter;
use Illuminate\Http\Request;

class CounterController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'counters' => Counter::with('branch')->get()
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'branch_id' => 'required|exists:branches,id',
        'user_id' => 'required|exists:users,id',
    ]);

    $counter = Counter::create([
        'name' => $request->name,
        'branch_id' => $request->branch_id,
        'user_id' => $request->user_id,
    ]);

    return response()->json([
        'success' => true,
        'counter' => $counter
    ], 201);
}


    public function show($id)
    {
        $counter = Counter::with('branch')->find($id);

        if (!$counter) {
            return response()->json(['success'=>false,'message'=>'Counter not found'],404);
        }

        return response()->json(['success'=>true,'counter'=>$counter]);
    }

    public function update(Request $request, $id)
    {
        $counter = Counter::find($id);

        if (!$counter) {
            return response()->json(['success'=>false,'message'=>'Counter not found'],404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'branch_id' => 'sometimes|required|exists:branches,id',
        ]);

        $counter->update($request->all());

        return response()->json(['success'=>true,'counter'=>$counter]);
    }

    public function destroy($id)
    {
        $counter = Counter::find($id);

        if (!$counter) {
            return response()->json(['success'=>false,'message'=>'Counter not found'],404);
        }

        $counter->delete();

        return response()->json(['success'=>true,'message'=>'Counter deleted']);
    }
}

