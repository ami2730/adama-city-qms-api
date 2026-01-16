<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'branch_id',
        'user_id',
        'service_id',
    ];

    // Counter belongs to a branch
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Counter belongs to a staff user
  public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}

    // Counter serves ONE service
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // Tickets handled by this counter
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
