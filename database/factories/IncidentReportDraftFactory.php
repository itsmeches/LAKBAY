<?php

namespace Database\Factories;

use App\Models\ActivityVersion;
use App\Models\IncidentReportDraft;
use App\Models\User;
use App\Services\Reporting\IncidentReportBuilder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IncidentReportDraft> */
class IncidentReportDraftFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'activity_version_id' => fn () => ActivityVersion::firstOrFail()->id,
            'activity_id' => fn (array $attributes) => ActivityVersion::findOrFail($attributes['activity_version_id'])->activity_id,
            'institution_id' => fn (array $attributes) => ActivityVersion::findOrFail($attributes['activity_version_id'])->activity->institution_id,
            'title' => 'Incident '.fake()->word(), 'created_by' => User::factory(),
            'updated_by' => fn (array $attributes) => $attributes['created_by'],
            'context' => fn (array $attributes) => app(IncidentReportBuilder::class)->context(ActivityVersion::findOrFail($attributes['activity_version_id'])),
            'details' => ['official' => fake()->name(), 'position' => 'HEI President'],
        ];
    }
}
