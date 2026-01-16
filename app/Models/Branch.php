<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'location'];

    // A branch has many services
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    // Optional: helper to get all tickets in this branch through services
    public function tickets()
    {
        return $this->hasManyThrough(
            Ticket::class,    // Final model
            Service::class,   // Intermediate model
            'branch_id',      // Foreign key on services table
            'service_id',     // Foreign key on tickets table
            'id',             // Local key on branches table
            'id'              // Local key on services table
        );
    }

    // Optional: helper to get all counters in this branch through services
    public function counters()
    {
        return $this->hasManyThrough(
            Counter::class,   // Final model
            Service::class,   // Intermediate model
            'branch_id',      // Foreign key on services table
            'service_id',     // Foreign key on counters table
            'id',             // Local key on branches table
            'id'              // Local key on services table
        );
    }
}


