<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Models\User;
use App\Services\Access;
use App\Services\Compliance\ClassificationPresentation;

class ConfirmActivityClassification
{
    public function execute(User $actor, Activity $activity, int $expectedVersionId, string $reason): Activity
    {
        app(Access::class)->authorize($actor, $activity->institution_id, 'submissions.submit', true);
        $version = $activity->fresh()->latestVersion;
        $assessment = ClassificationPresentation::latest($activity->fresh());
        abort_unless($assessment && $assessment->state === 'recommended', 422, 'Save an unconfirmed assessment before confirming it.');
        $data = $version->only(['category', 'academic_nature', 'activity_type', 'initiator', 'title', 'description', 'learning_objectives', 'program', 'responsible_office', 'venue', 'student_count', 'other_count', 'conditions', 'authorization', 'contracts', 'preparation', 'cloca', 'diagnostic', 'racp', 'annex_j']);
        $data += $activity->only(['institution_id', 'academic_period_id', 'calendar_id', 'campus_id']);
        foreach (['starts_at', 'ends_at', 'departure_at', 'return_at'] as $field) {
            $data[$field] = $version->$field?->timezone(config('lakbay.timezone'))->format('Y-m-d H:i:s');
        }
        $data['personnel'] = $version->personnel->toArray();
        $data['itinerary'] = $version->itineraryItems->map(fn ($item) => ['starts_at' => $item->starts_at->timezone(config('lakbay.timezone'))->format('Y-m-d H:i:s')] + $item->only(['description', 'location', 'locality', 'dot_accreditation', 'responsible_person']))->all();
        $data['classification'] = $assessment->facts['context'];
        unset($data['classification']['original_calendar_inclusion']);
        $data['classification'] = array_replace($data['classification'], ['rule_set_id' => $assessment->classification_rule_set_id, 'expected_version_id' => $expectedVersionId, 'confirm' => true, 'reason' => $reason]);

        return app(SaveActivity::class)->execute($actor, $data, $activity);
    }
}
