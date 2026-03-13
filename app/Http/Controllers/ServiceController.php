<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();
        
        $services = Service::when($user && $user->role === 'admin', function ($q) use ($user) {
            $q->where('branch_id', $user->branch_id);
        })->get();

        return response()->json([
            'success' => true,
            'services' => $services
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:2',
            'avg_time' => 'sometimes|integer|min:0',
        ]);

        if ($user->role === 'admin' && $request->branch_id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only create services for your own branch'], 403);
        }

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
        $user = $request->user();
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['success'=>false,'message'=>'Service not found'],404);
        }

        if ($user->role === 'admin' && $service->branch_id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only update services in your own branch'], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'prefix' => 'sometimes|required|string|max:2',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'avg_time' => 'sometimes|required|integer|min:0',
        ]);

        // Prevent admin from changing the branch of a service
        if ($user->role === 'admin' && $request->has('branch_id') && $request->branch_id != $user->branch_id) {
             return response()->json(['success' => false, 'message' => 'Unauthorized: You cannot change the branch of this service'], 403);
        }

        $service->update($request->all());

        return response()->json(['success'=>true,'service'=>$service]);
    }

 

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found'
            ], 404);
        }

        if ($user->role === 'admin' && $service->branch_id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only delete services in your own branch'], 403);
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

