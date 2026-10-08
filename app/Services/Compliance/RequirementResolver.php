<?php

namespace App\Services\Compliance;

use App\Models\ActivityVersion;
use App\Models\ClassificationRuleSet;
use App\Models\PolicyVersion;

class RequirementResolver
{
    public const PREDICATES = ['always', 'has_student_costs', 'requires_accommodation', 'external_invitation', 'higher_risk', 'venue_accreditation', 'medical_risk', 'has_incident', 'has_changes'];

    public function applies(string $predicate, array $conditions): bool
    {
        return match ($predicate) {
            'always' => true,'has_student_costs' => (bool) ($conditions['has_student_costs'] ?? false),
            'requires_accommodation' => (bool) ($conditions['requires_accommodation'] ?? false),
            'external_invitation' => (bool) ($conditions['external_invitation'] ?? false),
            'venue_accreditation' => (bool) (($conditions['requires_accommodation'] ?? false) || ($conditions['venue_requires_accreditation'] ?? false)),
            'medical_risk' => (bool) (($conditions['strenuous'] ?? false) || ($conditions['medical_clearance_relevant'] ?? false) || ($conditions['hazardous_destination'] ?? false)),
            'has_incident' => (bool) ($conditions['has_incident'] ?? false),
            'has_changes' => (bool) ($conditions['has_changes'] ?? false),
            'higher_risk' => (bool) (($conditions['strenuous'] ?? false) || ($conditions['vulnerable_participants'] ?? false) || ($conditions['hazardous_destination'] ?? false)),
            default => throw new \InvalidArgumentException('Unknown applicability predicate.')
        };
    }

    public function requirementCategory(int $declared, array $conditions, ?ClassificationRuleSet $rules = null): int
    {
        if ($declared !== 3) {
            return $declared;
        }
        $configuration = $rules?->configuration() ?? [];
        foreach ($configuration['category_3_escalation']['triggers'] ?? ['strenuous', 'vulnerable_participants', 'requires_accommodation', 'long_distance', 'class_size'] as $key) {
            if ($conditions[$key] ?? false) {
                return (int) ($configuration['category_3_escalation']['requirement_category'] ?? 2);
            }
        }

        return 3;
    }

    public function resolve(ActivityVersion $activity, PolicyVersion $policy, ?string $stage = null)
    {
        return $policy->requirements()->with('requirementDefinition')->where('category', $activity->requirement_category)
            ->when($stage && PolicyRules::isCmo($policy), fn ($query) => $query->where('stage', $stage))
            ->get()->filter(fn ($rule) => $this->applies($rule->predicate, $activity->conditions));
    }
}
