<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceHealthLog extends Model
{
    protected $fillable = [
        'device_id', 'status', 'response_time_ms', 'error_message',
    ];

    protected $casts = [
        'response_time_ms' => 'integer',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
