<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDelivery extends Model
{
    protected $table = 'notification_deliveries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['delivered_at' => 'immutable_datetime'];
    }
}
