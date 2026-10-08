<?php

namespace Database\Factories;

use App\Models\ActivityVersion;
use App\Models\ClassificationAssessment;
use App\Models\ClassificationRuleSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassificationAssessment>
 */
class ClassificationAssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_version_id' => fn () => ActivityVersion::firstOrFail()->id,
            'classification_rule_set_id' => ClassificationRuleSet::factory(),
            'event_key' => fake()->uuid(), 'state' => 'recommended', 'declared_category' => 1, 'recommended_category' => 2,
            'facts' => [], 'changes' => [], 'requirements_before' => [], 'requirements_after' => [],
            'timing' => ['rcd_anchor' => 'unresolved'], 'source_references' => ['CMO No. 24 §13.B.3, PDF p.6'],
            'reason' => 'Fictional schedule change', 'recorded_by' => User::factory(), 'occurred_at' => now(),
        ];
    }
}
