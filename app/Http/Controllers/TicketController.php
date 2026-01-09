<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TicketController extends Controller
{
    // 📌 LIST ALL TICKETS (Optional for admin)
    public function index()
    {
        $tickets = Ticket::with(['branch', 'service', 'user', 'counter'])->get();

        return response()->json([
            'success' => true,
            'tickets' => $tickets
        ]);
    }

    // 📌 CREATE TICKET
    public function store(Request $request)
    {
        $request->validate([
            'branch_id'  => 'required|exists:branches,id',
            'service_id' => 'required|exists:services,id',
            'fid'    => 'nullable|exists:users,fid',
        ]);

        $ticket = Ticket::create([
            'branch_id'  => $request->branch_id,
            'service_id' => $request->service_id,
            'fid'    => $request->user_id,
            'number'     => $this->generateTicketNumber($request->branch_id, $request->service_id),
            'status'     => 'waiting',
        ]);

        // Calculate how many tickets are ahead in the queue
        $ticketsAhead = Ticket::where('branch_id', $ticket->branch_id)
            ->where('service_id', $ticket->service_id)
            ->where('status', 'waiting')
            ->where('created_at', '<', $ticket->created_at)
            ->count();

        // 🔔 Real-time broadcast (optional)
        // event(new TicketCreated($ticket));

        return response()->json([
            'success' => true,
            'ticket' => $ticket,
            'tickets_ahead' => $ticketsAhead
        ], 201);
    }

    // 📌 SHOW TICKET
    public function show($id)
    {
        $ticket = Ticket::with(['branch', 'service'])->findOrFail($id);
        
        // Calculate tickets ahead for this ticket if it's waiting
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

    // 📌 CALL NEXT TICKET (for counter)
    public function callNext(Request $request)
    {
        $counter = $request->user()->counter;

        $ticket = Ticket::where('branch_id', $counter->branch_id)
            ->where('service_id', $counter->service_id)
            ->where('status', 'waiting')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();

        if (!$ticket) {
            abort(404, 'No tickets in queue');
        }

        $ticket->update([
            'status'     => 'called',
            'counter_id' => $counter->id,
            'called_at'  => now(),
        ]);

        return response()->json([
            'success' => true,
            'ticket' => $ticket
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
    public function skip(Ticket $ticket, Request $request)
    {
        // Only waiting or called tickets can be skipped
        if (!in_array($ticket->status, ['waiting','called'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket cannot be skipped'
            ], 400);
        }

        $ticket->update([
            'status' => 'skipped'
        ]);

        // Optional: auto-call next ticket for the same counter
        $counter = $request->user()->counter ?? null;
        $nextTicket = null;

        if ($counter) {
            $nextTicket = Ticket::where('branch_id', $counter->branch_id)
                ->where('service_id', $counter->service_id)
                ->where('status', 'waiting')
                ->orderBy('created_at')
                ->first();

            if ($nextTicket) {
                $nextTicket->update([
                    'status'     => 'called',
                    'counter_id' => $counter->id,
                    'called_at'  => now(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'skipped_ticket' => $ticket,
            'next_ticket'    => $nextTicket
        ]);
    }

    // 🔢 SMART TICKET NUMBER GENERATOR
    protected function generateTicketNumber($branchId, $serviceId)
    {
        $service = Service::findOrFail($serviceId);

        // Prefix: custom or first 2 letters of service
        $prefix = strtoupper($service->prefix ?? Str::substr($service->name, 0, 2));

        // Day of month (01-31)
        $day = Carbon::now()->format('d');

        // Ensure unique number per branch + service + day
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
