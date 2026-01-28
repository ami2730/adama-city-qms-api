<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Service;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct()
{
    $this->middleware(['auth:sanctum','role:super_admin']);
}
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
