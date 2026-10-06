<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityVersion extends Model
{
    use Immutable;

    protected $table = 'activity_versions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'departure_at' => 'immutable_datetime', 'return_at' => 'immutable_datetime', 'conditions' => 'array', 'authorization' => 'array'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function personnel(): HasMany
    {
        return $this->hasMany(ActivityPersonnel::class);
    }

    public function itineraryItems(): HasMany
    {
        return $this->hasMany(ItineraryItem::class);
    }
}
