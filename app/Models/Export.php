<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    protected $table = 'exports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'expires_at' => 'immutable_datetime'];
    }
}
