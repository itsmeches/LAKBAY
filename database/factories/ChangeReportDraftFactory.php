<?php

namespace Database\Factories;

use App\Models\ActivityVersion;
use App\Models\ChangeReportDraft;
use App\Models\User;
use App\Services\Reporting\ChangeReportBuilder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChangeReportDraft> */
class ChangeReportDraftFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['original_version_id' => fn () => ActivityVersion::orderBy('id')->firstOrFail()->id,
            'revised_version_id' => fn (array $attributes) => ActivityVersion::where('activity_id', ActivityVersion::findOrFail($attributes['original_version_id'])->activity_id)->where('id', '!=', $attributes['original_version_id'])->latest('version')->firstOrFail()->id,
            'activity_id' => fn (array $attributes) => ActivityVersion::findOrFail($attributes['original_version_id'])->activity_id,
            'institution_id' => fn (array $attributes) => ActivityVersion::findOrFail($attributes['original_version_id'])->activity->institution_id,
            'title' => 'Change '.fake()->word(), 'created_by' => User::factory(), 'updated_by' => fn (array $attributes) => $attributes['created_by'],
            'context' => fn (array $attributes) => app(ChangeReportBuilder::class)->context(ActivityVersion::findOrFail($attributes['original_version_id']), ActivityVersion::findOrFail($attributes['revised_version_id']), null),
            'details' => ['reason' => 'Changed arrangements']];
    }
}
