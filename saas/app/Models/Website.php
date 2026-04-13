<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ai_content' => 'array',
        ];
    }
}
