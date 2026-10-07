<?php

namespace Database\Factories;

use App\Models\ActivityVersion;
use App\Models\CompletionReportDraft;
use App\Models\User;
use App\Services\Reporting\CompletionReportBuilder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompletionReportDraft> */
class CompletionReportDraftFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'activity_version_id' => fn () => ActivityVersion::firstOrFail()->id,
            'activity_id' => fn (array $attributes) => ActivityVersion::findOrFail($attributes['activity_version_id'])->activity_id,
            'institution_id' => fn (array $attributes) => ActivityVersion::findOrFail($attributes['activity_version_id'])->activity->institution_id,
            'title' => 'ROC '.fake()->word(), 'created_by' => User::factory(),
            'updated_by' => fn (array $attributes) => $attributes['created_by'],
            'context' => fn (array $attributes) => app(CompletionReportBuilder::class)->context(ActivityVersion::findOrFail($attributes['activity_version_id'])),
            'details' => ['incidents_encountered' => false, 'official' => fake()->name(), 'position' => 'HEI President'],
        ];
    }
}
