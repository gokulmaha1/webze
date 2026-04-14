<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $guarded = [];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }
}
