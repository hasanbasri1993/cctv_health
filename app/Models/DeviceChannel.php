<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DeviceChannel extends Model
{
    protected $fillable = [
        'device_id', 'channel_number', 'name', 'status',
        'last_status_change', 'signal_quality',
    ];

    protected $casts = [
        'last_status_change' => 'datetime',
        'channel_number' => 'integer',
        'signal_quality' => 'integer',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function alerts(): MorphMany
    {
        return $this->morphMany(Alert::class, 'alertable');
    }
}
