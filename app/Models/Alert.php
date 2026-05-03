<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alert extends Model
{
    protected $fillable = [
        'device_id', 'alertable_type', 'alertable_id', 'type',
        'severity', 'title', 'message', 'status', 'acknowledged_at',
        'acknowledged_by', 'resolved_at', 'last_notified_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'last_notified_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAcknowledged(): bool
    {
        return $this->status === 'acknowledged';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }
}
