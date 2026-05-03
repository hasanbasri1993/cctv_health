<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'name', 'ip_address', 'port', 'username', 'password',
        'model', 'firmware_version', 'status', 'last_seen_at',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'last_seen_at' => 'datetime',
        'port' => 'integer',
    ];

    protected $hidden = ['password'];

    public function channels(): HasMany
    {
        return $this->hasMany(DeviceChannel::class);
    }

    public function storages(): HasMany
    {
        return $this->hasMany(DeviceStorage::class);
    }

    public function healthLogs(): HasMany
    {
        return $this->hasMany(DeviceHealthLog::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
