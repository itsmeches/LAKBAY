<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calendar extends Model
{
    protected $table = 'calendars';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['effective_on' => 'immutable_date', 'annex_b' => 'array'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
