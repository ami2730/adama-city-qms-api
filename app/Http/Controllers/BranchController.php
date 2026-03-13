<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Service;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{

    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        $branches = Branch::when($user && $user->role === 'admin', function ($q) use ($user) {
            $q->where('id', $user->branch_id);
        })->get();

        return response()->json([
            'success' => true,
            'branches' => $branches
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'super_admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Only Super Admin can create branches'], 403);
        }

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

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['success' => false, 'message' => 'Branch not found'], 404);
        }

        if ($user->role === 'admin' && $branch->id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only view your own branch'], 403);
        }

        return response()->json(['success' => true, 'branch' => $branch]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['success' => false, 'message' => 'Branch not found'], 404);
        }

        if ($user->role === 'admin' && $branch->id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only update your own branch'], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $branch->update($request->all());

        return response()->json(['success' => true, 'branch' => $branch]);
    }


    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'super_admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Only Super Admin can delete branches'], 403);
        }

        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            // 1️⃣ get all services under this branch
            $services = Service::where('branch_id', $branch->id)->get();

            foreach ($services as $service) {

                // 2️⃣ delete tickets of the service
                DB::table('tickets')
                    ->where('service_id', $service->id)
                    ->delete();

                // 3️⃣ delete counters of the service
                DB::table('counters')
                    ->where('service_id', $service->id)
                    ->delete();
            }

            // 4️⃣ delete services of the branch
            Service::where('branch_id', $branch->id)->delete();

            // 5️⃣ delete branch
            $branch->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch, services, tickets, and counters deleted successfully'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete branch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
