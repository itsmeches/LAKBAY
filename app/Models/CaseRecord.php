<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseRecord extends Model
{
    protected $table = 'case_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['opened_at' => 'immutable_datetime', 'closed_at' => 'immutable_datetime', 'legal_hold' => 'boolean'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function jurisdictionRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'jurisdiction_region_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CaseEvent::class);
    }
}
