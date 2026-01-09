<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'branches' => Branch::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $branch = Branch::create($request->all());

        return response()->json([
            'success' => true,
            'branch' => $branch
        ], 201);
    }

    public function show($id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['success'=>false,'message'=>'Branch not found'],404);
        }

        return response()->json(['success'=>true,'branch'=>$branch]);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['success'=>false,'message'=>'Branch not found'],404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $branch->update($request->all());

        return response()->json(['success'=>true,'branch'=>$branch]);
    }

    public function destroy($id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['success'=>false,'message'=>'Branch not found'],404);
        }

        $branch->delete();

        return response()->json(['success'=>true,'message'=>'Branch deleted']);
    }
}
