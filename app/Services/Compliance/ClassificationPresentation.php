<?php

namespace App\Services\Compliance;

use App\Models\Activity;
use App\Models\ClassificationAssessment;
use App\Models\ClassificationRuleSet;
use App\Models\Submission;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ClassificationPresentation
{
    public static function latest(Activity $activity): ?ClassificationAssessment
    {
        return Schema::hasTable('classification_assessments')
            ? ClassificationAssessment::where('activity_version_id', $activity->latestVersion?->id)->first() : null;
    }

    public static function describe(array $assessment): string
    {
        $timing = $assessment['timing'] ?? [];
        $requirements = collect($assessment['requirements_after'] ?? [])->map(fn ($row) => ($row['document_code'] ?? $row['code'] ?? '').' / '.($row['stage'] ?? ''))->unique()->implode(', ');
        $before = collect($assessment['requirements_before'] ?? [])->map(fn ($row) => ($row['document_code'] ?? $row['code'] ?? 'Rule #'.($row['policy_requirement_id'] ?? '?')).' '.($row['evidence_status'] ?? $row['stage'] ?? ''))->unique()->implode(', ');

        return 'Declared Category '.($assessment['declared_category'] ?? '?').'; requirement treatment Category '.($assessment['recommended_category'] ?? '?').'; '.($assessment['state'] ?? 'historical').'. '
            .'Reason: '.($assessment['reason'] ?? '').'. Assessment #'.($assessment['id'] ?? '?').'; rule set #'.($assessment['classification_rule_set_id'] ?? '?').'; baseline receipt #'.($assessment['baseline_submission_id'] ?? 'none').'; baseline version #'.($assessment['baseline_version_id'] ?? 'none').'. '
            .'Recorded by #'.($assessment['recorded_by'] ?? '?').'; confirmed by #'.($assessment['confirmed_by'] ?? 'pending').' at '.self::localTime($assessment['confirmed_at'] ?? null).'. '
            .'Requirements: '.$requirements.'. '
            .(! empty($timing['insufficient_lead']) ? 'WARNING: insufficient pre-activity lead; receipt is not permission to conduct. ' : '')
            .'Lead reference: '.self::localTime($timing['lead_due_at'] ?? null).'. '
            .(! empty($timing['immediate_notice_required']) ? 'Report changes immediately using Annex I; preparing a report is not filing it. ' : '')
            .'Formal RCD deadline anchor: '.($timing['rcd_anchor'] ?? 'unresolved').'. '
            .'Missing facts: '.implode('; ', $assessment['facts']['missing'] ?? []).'. '
            .'Changes: '.json_encode($assessment['changes'] ?? [], JSON_UNESCAPED_UNICODE).'. '
            .'Requirements before: '.$before.'. '
            .'Source: '.json_encode($assessment['source_references'] ?? [], JSON_UNESCAPED_UNICODE);
    }

    private static function localTime(?string $value): string
    {
        return $value ? CarbonImmutable::parse($value)->timezone(config('lakbay.timezone'))->format('d M Y H:i').' Asia/Manila' : 'unresolved / pending';
    }

    public static function history(Activity $activity): string
    {
        if (! Schema::hasTable('classification_assessments')) {
            return 'No assessment schema installed; historical classification is preserved.';
        }

        return ClassificationAssessment::whereIn('activity_version_id', $activity->versions()->select('id'))->latest('id')->get()
            ->map(fn ($assessment) => self::describe($assessment->toArray()))->implode("\n\n") ?: 'No saved assessment. Historical records are not reconstructed.';
    }

    public static function submission(Submission $submission): string
    {
        return collect($submission->snapshot['activities'] ?? [])->map(fn ($version) => ($version['title'] ?? '').': '.(! empty($version['classification_assessment'])
            ? self::describe($version['classification_assessment']) : 'Historical submission: no assessment snapshot; original requirements/verdicts retained.'))->implode("\n\n");
    }

    public static function ruleOptions(): array
    {
        if (! Schema::hasTable('classification_rule_sets')) {
            return [];
        }

        return ClassificationRuleSet::with('policyVersion')->get()->filter(function ($set) {
            if (empty($set->configuration()['revision']) || empty($set->configuration()['eligibility'])) {
                return false;
            }
            try {
                app(PolicyRules::class)->authorizeSubmission(app(PolicyRules::class)->forDraft($set->policyVersion));

                return true;
            } catch (ValidationException) {
                return false;
            }
        })->mapWithKeys(fn ($set) => [$set->id => $set->code.' / '.$set->policyVersion->status])->all();
    }
}
