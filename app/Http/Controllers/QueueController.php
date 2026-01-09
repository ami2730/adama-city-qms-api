<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    // List current queue for a branch & service
    public function index(Request $request)
    {
        $request->validate([
            'branch_id'  => 'required|exists:branches,id',
            'service_id' => 'required|exists:services,id',
        ]);

        $queue = Ticket::with(['user', 'branch', 'service'])
            ->where('branch_id', $request->branch_id)
            ->where('service_id', $request->service_id)
            ->where('status', 'waiting')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'queue' => $queue
        ]);
    }

    // Call next ticket at a counter
    public function callNext(Request $request)
    {
        $request->validate([
            'branch_id'  => 'required|exists:branches,id',
            'service_id' => 'required|exists:services,id',
            'counter_id' => 'required|exists:counters,id',
        ]);

        $ticket = Ticket::where('branch_id', $request->branch_id)
            ->where('service_id', $request->service_id)
            ->where('status', 'waiting')
            ->orderBy('created_at')
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'No tickets in the queue'
            ], 404);
        }

        $ticket->update([
            'status' => 'called',
            'called_at' => now(),
            'counter_id' => $request->counter_id
        ]);

        return response()->json([
            'success' => true,
            'ticket' => $ticket
        ]);
    }

    // Serve ticket
    public function serve(Request $request, $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket || $ticket->status != 'called') {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not available to serve'
            ], 404);
        }

        $ticket->update([
            'status' => 'served',
            'served_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'ticket' => $ticket
        ]);
    }

    // Skip ticket
    public function skip(Request $request, $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket || $ticket->status != 'called') {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not available to skip'
            ], 404);
        }

        $ticket->update([
            'status' => 'skipped'
        ]);

        return response()->json([
            'success' => true,
            'ticket' => $ticket
        ]);
    }
}

