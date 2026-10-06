<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Activity extends Model
{
    protected $table = 'activities';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['actual_started_at' => 'immutable_datetime', 'actual_ended_at' => 'immutable_datetime'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class, 'calendar_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ActivityVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ActivityVersion::class)->latestOfMany('version');
    }
}
