<?php

namespace App\Services\Compliance;

use App\Models\Activity;
use App\Models\ActivityVersion;
use App\Models\ClassificationAssessment;
use App\Models\ClassificationRuleSet;
use App\Models\Submission;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ClassificationEvaluator
{
    public function evaluate(Activity $activity, array $facts, ClassificationRuleSet $set, array $context): array
    {
        $rules = $set->configuration();
        if (empty($rules['revision']) || empty($rules['eligibility'])) {
            throw ValidationException::withMessages(['classification.rule_set_id' => 'Choose an evaluation-ready rule set.']);
        }
        $policy = app(PolicyRules::class)->forDraft($set->policyVersion);
        app(PolicyRules::class)->authorizeSubmission($policy);
        $previous = $activity->versions()->latest('version')->first();
        $previousAssessment = $previous ? ClassificationAssessment::where('activity_version_id', $previous->id)->latest('id')->first() : null;
        $receipt = $this->receipt($activity, $context);
        $baseline = $receipt?->activityVersions()->where('activity_versions.activity_id', $activity->id)->first();
        if (! $baseline && ! empty($context['approved_baseline_version_id'])) {
            $baseline = $activity->versions()->findOrFail($context['approved_baseline_version_id']);
            if (empty($baseline->authorization['official']) || empty($baseline->authorization['date'])) {
                throw ValidationException::withMessages(['classification.approved_baseline_version_id' => 'The selected baseline has no recorded HEI authorization.']);
            }
        }
        if (! $baseline) {
            $baseline = $activity->versions()->orderBy('version')->get()->first(fn ($version) => ! empty($version->authorization['official']) && ! empty($version->authorization['date']));
        }
        $original = $baseline ? $this->facts($baseline) : null;
        $prior = $previous ? $this->facts($previous) : null;
        $context['original_calendar_inclusion'] = $receipt?->calendar_id !== null && $receipt !== null;
        $originalCalendar = $activity->calendar_id ? Submission::where('calendar_id', $activity->calendar_id)->whereNotNull('submitted_at')->oldest('submitted_at')->oldest('id')->first() : null;
        $changes = $original ? $this->differences($original, $facts, $rules, $context) : [];
        $sequential = $prior ? $this->differences($prior, $facts, $rules, $context) : [];
        $declared = (int) $facts['category'];
        $recommended = app(RequirementResolver::class)->requirementCategory($declared, $facts['conditions'] ?? [], $set);
        $missing = [];
        if ($declared === 1) {
            $eligibility = $rules['eligibility']['category_1'];
            $planned = $context['planned_on'] ?? null;
            $regular = (bool) ($context['regular_process_confirmed'] ?? false);
            $calendar = $activity->calendar_id !== null;
            if ($eligibility['requires_planned_before_year'] && ! $planned) {
                $missing[] = 'Record the planning date for Category 1; absence of evidence is not proof of late planning.';
            }
            if ($eligibility['requires_regular_process'] && ! array_key_exists('regular_process_confirmed', $context)) {
                $missing[] = 'Confirm whether the activity follows the regular institutional process.';
            }
            if (($eligibility['requires_planned_before_year'] && (! $planned || CarbonImmutable::parse($planned)->gte($activity->academicPeriod->starts_on))) ||
                ($eligibility['requires_regular_process'] && ! $regular) || ($eligibility['requires_calendar'] && ! $calendar)) {
                $recommended = (int) $rules['revision']['new_proposal_requirement_category'];
            }
            if (! $receipt && $activity->academicPeriod->starts_on->lte(now()->timezone(config('lakbay.timezone'))->startOfDay())) {
                $recommended = (int) $rules['revision']['new_proposal_requirement_category'];
            }
            if ($originalCalendar && ! $originalCalendar->activityVersions()->where('activity_versions.activity_id', $activity->id)->exists()) {
                $recommended = (int) $rules['revision']['new_proposal_requirement_category'];
            }
        }
        if ($declared === 3 && ($rules['eligibility']['category_3']['requires_supervision_reference'] ?? false) && empty(trim($context['student_supervision_reference'] ?? ''))) {
            $missing[] = 'Record faculty/HEI supervision and approval reference for Category 3.';
        }
        if ($declared === 3 && ($rules['eligibility']['category_3']['requires_explicit_trigger_facts'] ?? false)) {
            foreach ($rules['category_3_escalation']['triggers'] as $trigger) {
                if (! array_key_exists($trigger, $facts['conditions'] ?? [])) {
                    $missing[] = 'Confirm the activity fact '.$trigger.'; missing information is not a negative answer.';
                }
            }
        }
        if ($declared === 3 && $receipt?->calendar_id) {
            $missing[] = 'Category 3 must be outside the original institutional calendar; explain the source classification.';
        }
        foreach ($changes as $change) {
            if ($change['outcome'] === $rules['revision']['category_2_outcome'] && $declared === 1) {
                $recommended = (int) $rules['revision']['new_proposal_requirement_category'];
            }
        }
        $mode = $activity->actual_ended_at ? 'after' : ($activity->actual_started_at ? 'during' : ($context['phase'] ?? 'before'));
        if ($mode !== 'before' && $previous) {
            $recommended = $previous->requirement_category;
        }
        $reversion = $baseline && $previous && $changes === [] && $sequential !== [];
        $downgrade = $previous && $recommended < $previous->requirement_category;
        $triggerRemoval = $previous && $previous->category === 3 && $previous->requirement_category === 2 && $recommended === 3;
        if (($reversion && ! $rules['reversion']['restore_original_treatment']) || ($triggerRemoval && ! $rules['reversion']['reassess_removed_triggers'])) {
            $recommended = $previous->requirement_category;
        }
        $reasonRequired = $declared !== $recommended || $reversion || $downgrade || $triggerRemoval || ($previous && ($previous->requirement_category !== $recommended || $previous->category !== $declared)) || $changes !== [];
        $reportable = collect($changes)->contains(fn ($change) => in_array($change['outcome'], $rules['revision']['rcd_outcomes'], true));
        $revertedReportable = $reversion && collect($sequential)->contains(fn ($change) => in_array($change['outcome'], $rules['revision']['rcd_outcomes'], true));
        $rcd = $baseline && ($reportable || $revertedReportable);
        $due = app(DeadlineCalculator::class)->calculate($rules['timing']['lead_type'], (int) $rules['timing']['lead_amount'], $facts['starts_at'], config('lakbay.holiday_calendar_version'), $activity->campus?->locality);
        $originalDue = $baseline ? app(DeadlineCalculator::class)->calculate($rules['timing']['lead_type'], (int) $rules['timing']['lead_amount'], $baseline->starts_at, $receipt?->holiday_calendar_version ?? config('lakbay.holiday_calendar_version'), $activity->campus?->locality)->toIso8601String() : null;
        $timing = ['phase' => $mode, 'lead_due_at' => $due->toIso8601String(), 'insufficient_lead' => now()->gt($due),
            'scheduled_start_passed' => now()->gte(CarbonImmutable::parse($facts['starts_at'])),
            'holiday_calendar_version' => config('lakbay.holiday_calendar_version'), 'rcd_anchor' => $rules['timing']['rcd_anchor'],
            'rcd_due_at' => null, 'immediate_notice_required' => (bool) $rcd, 'lead_reference' => $rules['timing']['lead_reference'],
            'late_intake' => $rules['timing']['late_intake']];
        $timing['original_lead_due_at'] = $previousAssessment?->timing['lead_due_at'] ?? $originalDue;
        $timing['original_holiday_calendar_version'] = $previousAssessment?->timing['holiday_calendar_version'] ?? $receipt?->holiday_calendar_version;
        if ($mode !== 'before' && ! $activity->actual_started_at && (empty(trim($context['conduct_reference'] ?? '')) || empty($context['conduct_at']))) {
            $missing[] = 'Record actual conduct date/time and its evidence reference for a during/after change; schedule alone does not prove execution.';
        }
        $before = $previousAssessment?->requirements_after ?? [];
        if ($receipt && (! $previousAssessment || $previousAssessment->activity_version_id === $baseline->id)) {
            $before = $receipt->requirements()->where('activity_version_id', $baseline->id)->get()->map(fn ($item) => $item->only(['policy_requirement_id', 'evidence_status', 'rule_snapshot', 'due_at']))->all();
        }
        $after = $policy->requirements()->with('requirementDefinition.documentType')->where('category', $recommended)->get()
            ->filter(fn ($rule) => app(RequirementResolver::class)->applies($rule->predicate, $facts['conditions'] ?? []))
            ->map(fn ($rule) => ['policy_version_id' => $policy->id, 'code' => $rule->requirementDefinition->code, 'document_code' => $rule->requirementDefinition->documentType?->code, 'stage' => $rule->stage, 'rule_snapshot' => $rule->toArray()])->values()->all();

        return ['baseline' => $baseline, 'receipt' => $receipt, 'previous_assessment' => $previousAssessment,
            'recommended_category' => $recommended, 'changes' => ['cumulative' => $changes, 'sequential' => $sequential, 'reversion' => (bool) $reversion, 'trigger_removal' => (bool) $triggerRemoval],
            'reason_required' => $reasonRequired, 'missing' => $missing, 'timing' => $timing, 'requirements_before' => $before,
            'requirements_after' => $after, 'facts' => $facts, 'context' => $context, 'policy_version_id' => $policy->id,
            'original_calendar_submission_id' => $originalCalendar?->id];
    }

    public function facts(ActivityVersion $version): array
    {
        return $version->only(['category', 'starts_at', 'ends_at', 'departure_at', 'return_at', 'title', 'description', 'learning_objectives', 'academic_nature', 'activity_type', 'initiator', 'venue', 'student_count', 'other_count', 'conditions', 'preparation', 'contracts', 'cloca', 'annex_j', 'responsible_office', 'program']) +
            $version->activity->only(['institution_id', 'academic_period_id', 'calendar_id', 'campus_id']) +
            ['personnel' => $version->personnel->map(fn ($person) => $person->only(['name', 'role', 'position', 'contact']))->all(),
                'itinerary' => $version->itineraryItems->sortBy('sequence')->map(fn ($item) => $item->only(['starts_at', 'description', 'location', 'locality', 'dot_accreditation', 'responsible_person']))->values()->all()];
    }

    private function receipt(Activity $activity, array $context): ?Submission
    {
        $query = Submission::whereHas('activityVersions', fn ($q) => $q->where('activity_versions.activity_id', $activity->id))->whereNotNull('submitted_at');
        if (! empty($context['baseline_submission_id'])) {
            return $query->findOrFail($context['baseline_submission_id']);
        }

        return $query->orderByRaw('CASE WHEN calendar_id IS NULL THEN 1 ELSE 0 END')->orderBy('submitted_at')->orderBy('id')->first();
    }

    private function differences(array $old, array $new, array $rules, array $context): array
    {
        $differences = [];
        foreach ($rules['materiality'] as $group => $rule) {
            foreach ($rule['paths'] ?? [] as $path) {
                $before = $this->normalize(data_get($old, $path));
                $after = $this->normalize(data_get($new, $path));
                if (str_starts_with($path, 'conditions.')) {
                    $before = (bool) $before;
                    $after = (bool) $after;
                }
                if (in_array($path, ['student_count', 'other_count'], true)) {
                    $before = (int) $before;
                    $after = (int) $after;
                }
                if (preg_match('/(?:starts_at|ends_at|departure_at|return_at)$/', $path)) {
                    $before = $this->dates(data_get($old, $path));
                    $after = $this->dates(data_get($new, $path));
                }
                if ($before === $after) {
                    continue;
                }
                $outcome = $rule['default_outcome'];
                if (isset($rule['automatic_context_predicate']) && $this->contextApplies($rule['automatic_context_predicate'], $old, $new, $rules, $context)) {
                    $outcome = $rule['automatic_context_outcome'];
                }
                if ($choice = $context['contextual_choices'][$group] ?? null) {
                    if (! in_array($choice, $rules['revision']['contextual_choices'][$group] ?? [], true) || empty(trim($context['reason'] ?? ''))) {
                        throw ValidationException::withMessages(['classification.contextual_choices' => 'Use a configured context and record its justification.']);
                    }
                    $outcome = $rule['contextual_outcome'];
                }
                if ($outcome === 'new_preparation_required') {
                    throw ValidationException::withMessages(['classification' => 'Institutional/calendar identity requires a separate preparation.']);
                }
                $differences[] = ['group' => $group, 'path' => $path, 'previous' => $before, 'revised' => $after, 'outcome' => $outcome,
                    'source_reference' => $rule['source_reference'], 'decision_source' => $rule['decision_source'], 'context' => $choice ?: null];
            }
        }

        return $differences;
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value)->utc()->toIso8601String();
        }
        if (is_array($value)) {
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map(fn ($item) => $this->normalize($item), $value);
        }

        return $value === '' ? null : $value;
    }

    private function dates(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->dates($item), $value);
        }

        return $value ? CarbonImmutable::parse($value, config('lakbay.timezone'))->utc()->toIso8601String() : null;
    }

    private function contextApplies(string $predicate, array $old, array $new, array $rules, array $context): bool
    {
        if ($predicate !== 'original_calendar_unchanged_schedule_scope_design') {
            throw ValidationException::withMessages(['classification' => 'Unknown configured classification predicate.']);
        }
        if (! ($context['original_calendar_inclusion'] ?? false)) {
            return false;
        }
        foreach (['schedule', 'scope', 'design'] as $group) {
            foreach ($rules['materiality'][$group]['paths'] as $path) {
                $datePath = preg_match('/(?:starts_at|ends_at|departure_at|return_at)$/', $path);
                $before = $datePath ? $this->dates(data_get($old, $path)) : $this->normalize(data_get($old, $path));
                $after = $datePath ? $this->dates(data_get($new, $path)) : $this->normalize(data_get($new, $path));
                if ($before !== $after) {
                    return false;
                }
            }
        }

        return true;
    }
}
