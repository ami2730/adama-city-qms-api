<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TicketController extends Controller
{
    // 📌 ADMIN: LIST ALL TICKETS
    public function index(Request $request)
    {
        $user = $request->user();

        $tickets = Ticket::with(['branch', 'service', 'user', 'counter'])
            ->when($user && in_array($user->role, ['admin', 'staff']), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            // Allow optional filtering by branch (if not restricted) and service
            ->when($request->branch_id, function ($q, $branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when($request->service_id, function ($q, $serviceId) {
                $q->where('service_id', $serviceId);
            })
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'tickets' => $tickets
        ]);
    }

    // 📌 PUBLIC: VIEW CURRENT QUEUE
    public function getQueue(Request $request)
    {
        $request->validate([
            'branch_id'  => 'required|exists:branches,id',
            'service_id' => 'required|exists:services,id',
        ]);

        $queue = Ticket::with(['user', 'counter'])
            ->where('branch_id', $request->branch_id)
            ->where('service_id', $request->service_id)
            ->whereIn('status', ['waiting', 'skipped','called'])
            ->orderBy('updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'queue' => $queue
        ]);
    }

    // 📌 CREATE TICKET (USER)
    public function store(Request $request)
    {
        $request->validate([
            'branch_id'  => 'required|exists:branches,id',
            'service_id' => 'required|exists:services,id',
            'fid'        => 'required',
            
        ]);

        $ticket = Ticket::create([
            'branch_id'  => $request->branch_id,
            'service_id' => $request->service_id,
            'fid'        => $request->fid,
            'number'     => $this->generateTicketNumber(
                $request->branch_id,
                $request->service_id
            ),
            'status'     => 'waiting',
            'updated_at' => now(),
        ]);

        $ticketsAhead = Ticket::where('branch_id', $ticket->branch_id)
            ->where('service_id', $ticket->service_id)
            ->whereIn('status', ['waiting','skipped'])
            ->where('updated_at', '<', $ticket->updated_at)
            ->count();

        return response()->json([
            'success' => true,
            'ticket' => $ticket,
            'tickets_ahead' => $ticketsAhead
        ], 201);
    }

    // 📌 SHOW SINGLE TICKET
    public function show($number)
{
    $ticket = Ticket::with(['branch', 'service', 'counter'])
        ->where('number', $number)
        ->firstOrFail();

    $ticketsAhead = 0;

    if ($ticket->status === 'waiting') {
        $ticketsAhead = Ticket::where('branch_id', $ticket->branch_id)
            ->where('service_id', $ticket->service_id)
            ->whereIn('status', ['waiting','skipped'])
            ->where('updated_at', '<', $ticket->updated_at)
            ->count();
    }

    return response()->json([
        'success' => true,
        'ticket' => $ticket,
        'tickets_ahead' => $ticketsAhead
    ]);
}
    // 📌 CALL NEXT TICKET (COUNTER STAFF)
  public function callNext(Request $request)
{
    $user = $request->user();

    // Check if user is staff
if (!in_array($user->role, ['staff','admin'])) {
    return response()->json([
        'success' => false,
        'message' => 'Only staff can call tickets'
    ], 403);
}


    // Get the counter assigned to this staff
    $counter = $user->counter;

    if (!$counter) {
        return response()->json([
            'success' => false,
            'message' => 'User is not assigned to a counter'
        ], 403);
    }

    // Find the next waiting ticket for this counter's branch and service
    $ticket = Ticket::where('branch_id', $counter->branch_id)
        ->where('service_id', $counter->service_id)
        ->whereIn('status', ['waiting','skipped'])
        ->orderBy('updated_at')
        ->lockForUpdate()
        ->first();

    if (!$ticket) {
        return response()->json([
            'success' => false,
            'message' => 'No tickets in the queue'
        ], 404);
    }

    // Update ticket as called
    $ticket->update([
        'status'     => 'called',
        'counter_id' => $counter->id,
        'called_at'  => now(),
        'updated_at' => now(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Ticket called successfully',
        'ticket'  => $ticket
    ]);
}


    // 📌 SERVE TICKET
    public function serve(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $counter = $user->counter;

        if ($ticket->status !== 'called') {
            return response()->json(['success' => false, 'message' => 'Ticket not called'], 400);
        }

        if ($user->role === 'staff' && (!$counter || $ticket->counter_id != $counter->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only serve tickets at your assigned counter'], 403);
        }

        if ($user->role === 'admin' && $ticket->branch_id != $user->branch_id) {
             return response()->json(['success' => false, 'message' => 'Unauthorized: You can only serve tickets in your own branch'], 403);
        }

        $ticket->update([
            'status'    => 'served',
            'updated_at' => now(),
            'served_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'ticket' => $ticket
        ]);
    }

    // 📌 SKIP TICKET
    public function skip(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $counter = $user->counter;

        if (!in_array($ticket->status, ['waiting', 'called', 'skipped'])) {
             return response()->json(['success' => false, 'message' => 'Ticket cannot be skipped'], 400);
        }

        if ($user->role === 'staff') {
            if ($ticket->status === 'called' && (!$counter || $ticket->counter_id != $counter->id)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: You can only skip tickets called at your assigned counter'], 403);
            }
            if ($ticket->status === 'waiting' && $ticket->branch_id != $user->branch_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: You can only skip tickets in your own branch'], 403);
            }
        }

        if ($user->role === 'admin' && $ticket->branch_id != $user->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only skip tickets in your own branch'], 403);
        }

        $ticket->update([
            'status' => 'skipped',
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'ticket' => $ticket
        ]);
    }

    // 🔢 TICKET NUMBER GENERATOR
    protected function generateTicketNumber($branchId, $serviceId)
    {
        $service = Service::findOrFail($serviceId);
        $prefix = strtoupper($service->prefix ?? Str::substr($service->name, 0, 2));
        $day = Carbon::now()->format('d');

        do {
            $random = random_int(1000, 9999);
            $number = "{$prefix}{$day}{$random}";
        } while (
            Ticket::where('branch_id', $branchId)
                ->where('service_id', $serviceId)
                ->whereDate('updated_at', Carbon::today())
                ->where('number', $number)
                ->exists()
        );

        return $number;
    }
}
