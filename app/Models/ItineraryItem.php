<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryItem extends Model
{
    use Immutable;

    protected $table = 'itinerary_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime'];
    }

    public function activityVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'activity_version_id');
    }
}
