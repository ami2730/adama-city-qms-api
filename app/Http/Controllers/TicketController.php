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
    public function index()
    {
        $tickets = Ticket::with(['branch', 'service', 'user', 'counter'])
            ->orderByDesc('created_at')
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

        $queue = Ticket::with(['user'])
            ->where('branch_id', $request->branch_id)
            ->where('service_id', $request->service_id)
            ->whereIn('status', ['waiting', 'called'])
            ->orderBy('created_at')
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
        ]);

        $ticketsAhead = Ticket::where('branch_id', $ticket->branch_id)
            ->where('service_id', $ticket->service_id)
            ->where('status', 'waiting')
            ->where('created_at', '<', $ticket->created_at)
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
            ->where('status', 'waiting')
            ->where('created_at', '<', $ticket->created_at)
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
        ->where('status', 'waiting')
        ->orderBy('created_at')
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
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Ticket called successfully',
        'ticket'  => $ticket
    ]);
}


    // 📌 SERVE TICKET
    public function serve(Ticket $ticket)
    {
        abort_if($ticket->status !== 'called', 400, 'Ticket not called');

        $ticket->update([
            'status'    => 'served',
            'served_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'ticket' => $ticket
        ]);
    }

    // 📌 SKIP TICKET
    public function skip(Ticket $ticket)
    {
        abort_if(!in_array($ticket->status, ['waiting', 'called']),
            400, 'Ticket cannot be skipped'
        );

        $ticket->update([
            'status' => 'skipped'
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
                ->whereDate('created_at', Carbon::today())
                ->where('number', $number)
                ->exists()
        );

        return $number;
    }
}
