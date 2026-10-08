<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Holiday extends Model
{
    protected $table = 'holidaies';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        foreach (['creating', 'updating', 'deleting'] as $event) {
            static::$event(function ($model) {
                $calendarVersions = array_filter([$model->calendar_version, $model->getOriginal('calendar_version')]);
                $assessmentReferences = Schema::hasTable('classification_assessments') && ClassificationAssessment::where(function ($query) use ($calendarVersions) {
                    foreach ($calendarVersions as $version) {
                        $query->orWhere('timing->holiday_calendar_version', $version)->orWhere('timing->original_holiday_calendar_version', $version);
                    }
                })->exists();
                if ($assessmentReferences || Submission::where('holiday_calendar_version', $model->calendar_version)->exists() || ($model->getOriginal('calendar_version') && Submission::where('holiday_calendar_version', $model->getOriginal('calendar_version'))->exists())) {
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
