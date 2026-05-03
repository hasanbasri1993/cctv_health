<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DeviceStorage extends Model
{
    protected $fillable = [
        'device_id', 'storage_id', 'name', 'type', 'capacity',
        'used_space', 'health_status', 'temperature',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'used_space' => 'integer',
        'storage_id' => 'integer',
        'temperature' => 'integer',
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
