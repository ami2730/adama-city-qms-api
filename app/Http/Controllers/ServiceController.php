<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'services' => Service::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:2',
        ]);

        $service = Service::create($request->all());

        return response()->json(['success'=>true,'service'=>$service],201);
    }

    public function show($id)
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['success'=>false,'message'=>'Service not found'],404);
        }

        return response()->json(['success'=>true,'service'=>$service]);
    }

    public function update(Request $request, $id)
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['success'=>false,'message'=>'Service not found'],404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'avg_time' => 'sometimes|required|integer|min:0',
        ]);

        $service->update($request->all());

        return response()->json(['success'=>true,'service'=>$service]);
    }

 

public function destroy($id)
{
    $service = Service::find($id);

    if (!$service) {
        return response()->json([
            'success' => false,
            'message' => 'Service not found'
        ], 404);
    }

    DB::beginTransaction();

    try {
        // 1️⃣ delete tickets of this service
        DB::table('tickets')
            ->where('service_id', $service->id)
            ->delete();

        // 2️⃣ delete counters of this service
        DB::table('counters')
            ->where('service_id', $service->id)
            ->delete();

        // 3️⃣ delete service itself
        $service->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Service, tickets, and counters deleted successfully'
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete service',
            'error' => $e->getMessage()
        ], 500);
    }
}

}

