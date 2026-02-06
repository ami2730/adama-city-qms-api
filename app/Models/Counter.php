<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a service counter in a branch.
 *
 * A counter is typically assigned to:
 * - one branch
 * - one staff member (user)
 * - one service type
 */
class Counter extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'branch_id',
        'user_id',
        'service_id',
    ];

    /**
     * Get the branch that owns this counter.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the staff member (user) assigned to this counter.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service that this counter is serving.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get all tickets that have been processed / are assigned to this counter.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
