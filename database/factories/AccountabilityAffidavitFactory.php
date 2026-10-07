<?php

namespace Database\Factories;

use App\Models\AccountabilityAffidavit;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Compliance\PolicyRules;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountabilityAffidavit>
 */
class AccountabilityAffidavitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'SAA '.fake()->word(),
            'created_by' => User::factory(),
            'updated_by' => fn (array $attributes) => $attributes['created_by'],
            'form_template_id' => FormTemplate::where('code', 'saa')->whereHas('policyVersion', fn ($query) => $query->where('code', PolicyRules::CMO_CODE))->value('id'),
            'details' => ['name' => fake()->name(), 'position' => 'HEI President', 'citizenship' => 'Filipino'],
        ];
    }
}
