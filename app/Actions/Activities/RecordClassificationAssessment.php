<?php

namespace App\Actions\Activities;

use App\Models\ActivityVersion;
use App\Models\ClassificationAssessment;
use App\Models\ClassificationRuleSet;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Validation\ValidationException;

class RecordClassificationAssessment
{
    public function execute(User $actor, ActivityVersion $version, ClassificationRuleSet $set, array $evaluation): ClassificationAssessment
    {
        app(Access::class)->authorize($actor, $version->activity->institution_id, 'activities.manage');
        abort_unless($actor->can('panel.hei') && app(Access::class)->owns($actor, $version->activity->institution_id), 403);
        abort_unless($version->requirement_category === $evaluation['recommended_category'] && $version->category === (int) $evaluation['facts']['category'], 422);
        abort_if($evaluation['baseline'] && $evaluation['baseline']->activity_id !== $version->activity_id, 403);
        abort_if($evaluation['receipt'] && $evaluation['receipt']->institution_id !== $version->activity->institution_id, 403);
        $context = $evaluation['context'];
        $confirmed = (bool) ($context['confirm'] ?? false);
        $reason = trim($context['reason'] ?? '');
        if ($confirmed) {
            app(Access::class)->authorize($actor, $version->activity->institution_id, $set->rules['eligibility']['confirmation_permission'], true);
            if ($evaluation['missing']) {
                throw ValidationException::withMessages(['classification' => implode(' ', $evaluation['missing'])]);
            }
            if ($evaluation['reason_required'] && $reason === '') {
                throw ValidationException::withMessages(['classification.reason' => 'Explain the revision, treatment change or reversion before confirming it.']);
            }
        }
        $assessment = ClassificationAssessment::create(['activity_version_id' => $version->id,
            'baseline_version_id' => $evaluation['baseline']?->id, 'baseline_submission_id' => $evaluation['receipt']?->id,
            'previous_assessment_id' => $evaluation['previous_assessment']?->id, 'classification_rule_set_id' => $set->id,
            'event_key' => 'activity-version-'.$version->id, 'state' => $confirmed ? 'confirmed' : 'recommended',
            'declared_category' => $version->category, 'recommended_category' => $evaluation['recommended_category'],
            'confirmed_category' => $confirmed ? $evaluation['recommended_category'] : null,
            'facts' => ['activity' => $evaluation['facts'], 'context' => $context, 'missing' => $evaluation['missing'], 'policy_version_id' => $evaluation['policy_version_id'], 'original_calendar_submission_id' => $evaluation['original_calendar_submission_id']],
            'changes' => $evaluation['changes'], 'requirements_before' => $evaluation['requirements_before'], 'requirements_after' => $evaluation['requirements_after'],
            'timing' => $evaluation['timing'], 'source_references' => $set->source,
            'reason' => $reason ?: 'Draft recommendation; confirmation outstanding.', 'recorded_by' => $actor->id,
            'confirmed_by' => $confirmed ? $actor->id : null, 'confirmed_at' => $confirmed ? now() : null, 'occurred_at' => now()]);
        Audit::record($actor, 'activity.classification.assessed', $version->activity, ['assessment_id' => $assessment->id, 'activity_version_id' => $version->id]);

        return $assessment;
    }
}
