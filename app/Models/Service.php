<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['branch_id', 'name', 'prefix'];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }
}
public function tickets()
{
    return $this->hasMany(Ticket::class);
}

public function counters()
{
    return $this->hasMany(Counter::class);
}

