<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cctv extends Model
{
    protected $fillable = [
        'name',
        'ip',
        'username',
        'password',
        'has_camera',
        'status',
        'test_result_list',
    ];

    protected $casts = [
        'test_result_list' => 'array',
    ];

    public function health(): HasMany
    {
        return $this->hasMany(Health::class);
    }
}
