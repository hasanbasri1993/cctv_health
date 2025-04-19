<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Health extends Model
{
    protected $fillable = [
        'cctv_id',
        'temprature',
        'powerOnDay',
        'allEvaluaingStatus',
        'active_cameras',
    ];

    public function cctv(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cctv::class);
    }
}
