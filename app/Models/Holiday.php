<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $table = 'holidaies';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        foreach (['creating', 'updating', 'deleting'] as $event) {
            static::$event(function ($model) {
                if (Submission::where('holiday_calendar_version', $model->calendar_version)->exists() || ($model->getOriginal('calendar_version') && Submission::where('holiday_calendar_version', $model->getOriginal('calendar_version'))->exists())) {
                    throw new \LogicException('Referenced holiday calendars are immutable. Use a new calendar version.');
                }
            });
        }
    }

    protected function casts(): array
    {
        return ['date' => 'immutable_date'];
    }
}
