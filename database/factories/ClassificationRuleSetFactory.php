<?php

namespace Database\Factories;

use App\Models\ClassificationRuleSet;
use App\Models\PolicyVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassificationRuleSet>
 */
class ClassificationRuleSetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'policy_version_id' => fn () => PolicyVersion::where('code', 'CMO24-2026')->firstOrFail()->id,
            'code' => 'TEST-'.fake()->uuid(), 'status' => 'proposed', 'rules' => [],
            'source' => ['section' => '13.B.3', 'pdf_page' => 6], 'decision_source' => 'Fictional test fixture only',
        ];
    }
}
