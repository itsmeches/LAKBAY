<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringItem extends Model
{
    protected $table = 'monitoring_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function monitoringRecord(): BelongsTo
    {
        return $this->belongsTo(MonitoringRecord::class, 'monitoring_record_id');
    }
}
