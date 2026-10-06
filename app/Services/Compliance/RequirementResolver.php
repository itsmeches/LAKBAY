<?php

namespace App\Services\Compliance;

use App\Models\ActivityVersion;
use App\Models\PolicyVersion;

class RequirementResolver
{
    public const PREDICATES = ['always', 'has_student_costs', 'requires_accommodation', 'external_invitation', 'higher_risk'];

    public function applies(string $predicate, array $conditions): bool
    {
        return match ($predicate) {
            'always' => true,'has_student_costs' => (bool) ($conditions['has_student_costs'] ?? false),
            'requires_accommodation' => (bool) ($conditions['requires_accommodation'] ?? false),
            'external_invitation' => (bool) ($conditions['external_invitation'] ?? false),
            'higher_risk' => (bool) (($conditions['strenuous'] ?? false) || ($conditions['vulnerable_participants'] ?? false) || ($conditions['hazardous_destination'] ?? false)),
            default => throw new \InvalidArgumentException('Unknown applicability predicate.')
        };
    }

    public function requirementCategory(int $declared, array $conditions): int
    {
        if ($declared !== 3) {
            return $declared;
        }
        foreach (['strenuous', 'vulnerable_participants', 'requires_accommodation', 'long_distance', 'class_size'] as $key) {
            if ($conditions[$key] ?? false) {
                return 2;
            }
        }

        return 3;
    }

    public function resolve(ActivityVersion $activity, PolicyVersion $policy)
    {
        return $policy->requirements()->with('requirementDefinition')->where('category', $activity->requirement_category)->get()->filter(fn ($rule) => $this->applies($rule->predicate, $activity->conditions));
    }
}
