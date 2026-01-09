<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'service_id',
        'fid',
        'number',
        'counter_id',
        'status',
        'called_at',
        'served_at'
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    // Optional: default values
    protected $attributes = [
        'status' => 'pending', // default status
    ];

    // Relationships

public function counter()
{
    return $this->belongsTo(\App\Models\Counter::class);
}  

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function service()
    {
        return $this->belongsTo(\App\Models\Service::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
