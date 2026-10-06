<?php

namespace App\Services\Compliance;

use App\Models\Holiday;
use Carbon\CarbonImmutable;

class DeadlineCalculator
{
    public function calculate(string $type, int $amount, \DateTimeInterface|string $anchor, string $calendarVersion, ?string $locality = null): CarbonImmutable
    {
        $date = CarbonImmutable::parse($anchor, config('lakbay.timezone'))->timezone(config('lakbay.timezone'));
        if ($type === 'calendar_days_before') {
            return $date->subDays($amount)->utc();
        }
        if ($type === 'calendar_days_after') {
            return $date->addDays($amount)->utc();
        }
        if ($type === 'hours_after') {
            return $date->addHours($amount)->utc();
        }
        if ($type !== 'working_days_before') {
            throw new \InvalidArgumentException('Unknown deadline rule.');
        }
        $holidays = Holiday::where('calendar_version', $calendarVersion)->where(fn ($q) => $q->where('scope', 'national')->when($locality, fn ($q) => $q->orWhere('locality', $locality)))->pluck('date')->map(fn ($v) => CarbonImmutable::parse($v)->toDateString())->all();
        for ($days = 0; $days < $amount;) {
            $date = $date->subDay();
            if (! $date->isWeekend() && ! in_array($date->toDateString(), $holidays, true)) {
                $days++;
            }
        }

        return $date->utc();
    }
}
