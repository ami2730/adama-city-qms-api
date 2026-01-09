<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;

    // Add user_id to fillable
    protected $fillable = [
        'name',
        'branch_id',
        'fid',
    ];

    // Relationship: Counter belongs to a Branch
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Counter belongs to a User
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // Optional: tickets assigned to this counter
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
