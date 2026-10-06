<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringRecord extends Model
{
    protected $table = 'monitoring_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['monitored_on' => 'immutable_date'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MonitoringItem::class);
    }
}
