<?php

namespace App\Services\Compliance;

use App\Models\Activity;
use App\Models\ActivityVersion;
use App\Models\Document;
use App\Models\PolicyRequirement;
use App\Models\PolicyVersion;
use App\Models\Submission;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PolicyRules
{
    public const CMO_CODE = 'CMO24-2026';

    public static function isCmo(?PolicyVersion $policy): bool
    {
        return $policy && ($policy->code === self::CMO_CODE || ($policy->parameters['source_cmo'] ?? null) === 24);
    }

    public function forDraft(PolicyVersion $policy): PolicyVersion
    {
        $visited = [];
        while ($next = PolicyVersion::where('parameters->supersedes_policy_version_id', $policy->id)->latest('id')->get()->first(function (PolicyVersion $successor): bool {
            try {
                $this->authorizeSubmission($successor);

                return true;
            } catch (ValidationException) {
                return false;
            }
        })) {
            if (isset($visited[$next->id])) {
                throw new \LogicException('Policy successor cycle.');
            }
            $visited[$policy->id] = true;
            $policy = $next;
        }

        return $policy;
    }

    /** @return list<int> */
    public function compatibleAnnualBaselines(PolicyVersion $policy): array
    {
        $ids = [$policy->id];
        $annual = static fn (PolicyVersion $version) => $version->requirements()->where('category', 1)->where('stage', 'annual_calendar')
            ->orderBy('requirement_definition_id')->get()->map(fn ($rule) => Arr::except($rule->getAttributes(), ['id', 'policy_version_id', 'created_at', 'updated_at']))->all();
        $expected = $annual($policy);
        while ($sourceId = $policy->parameters['supersedes_policy_version_id'] ?? null) {
            $source = PolicyVersion::find($sourceId);
            if (! $source || in_array($source->id, $ids, true) || $annual($source) !== $expected) {
                break;
            }
            $ids[] = $source->id;
            $policy = $source;
        }

        return $ids;
    }

    public static function hasVerifiedActivation(PolicyVersion $policy): bool
    {
        return $policy->status === 'confirmed' && $policy->publication_on && trim($policy->publication_reference ?? '') &&
            $policy->effective_on && $policy->effective_on->equalTo($policy->publication_on->addDays(15));
    }

    public function parameters(?PolicyVersion $policy): array
    {
        return $policy?->parameters ?? ['incident_hours' => 24, 'completion_hours' => 72, 'change_hours' => 72,
            'change_anchor' => 'changed_at', 'clearance_days_before' => 90, 'reconsideration_days' => 15];
    }

    public function forActivity(Activity $activity, ?CarbonImmutable $eventAt = null): ?PolicyVersion
    {
        $effective = PolicyVersion::where('status', 'confirmed')
            ->where('effective_on', '<=', ($eventAt ?? now())->timezone(config('lakbay.timezone'))->toDateString())->orderByDesc('effective_on')->orderByDesc('id')->get()->first(fn ($p) => self::isCmo($p) && self::hasVerifiedActivation($p));
        if ($effective) {
            return $effective;
        }
        $submission = Submission::whereHas('activityVersions', fn ($q) => $q->where('activity_versions.activity_id', $activity->id))
            ->where('status', '!=', 'superseded')->latest('id')->first();

        return $submission?->policyVersion ?? PolicyVersion::where('code', 'LAKBAY-JULY-2026-DRAFT')->first();
    }

    public function reportRule(?PolicyVersion $policy, string $type, int $category): ?PolicyRequirement
    {
        if (! self::isCmo($policy)) {
            return null;
        }
        $row = match ($type) {
            'completion' => 20, 'incident' => 21, 'change' => 22, default => throw new \InvalidArgumentException('Unknown report type.')
        };

        return $policy->requirements()->where('section_row', $row)->where('stage', 'post_activity')->where('category', $category)->first();
    }

    public function authorizeSubmission(PolicyVersion $policy): void
    {
        $testingDraft = app()->environment(['local', 'testing']) && config('lakbay.allow_draft_policy');
        if ($policy->status !== 'confirmed' && ! $testingDraft) {
            throw ValidationException::withMessages(['policy' => 'Official submissions require a confirmed, effective policy. Candidate testing is local/testing only.']);
        }
        if ($policy->status === 'confirmed' && self::isCmo($policy) && ! self::hasVerifiedActivation($policy)) {
            throw ValidationException::withMessages(['policy' => 'CMO 24 activation requires verified publication and publication-based effectivity.']);
        }
        if ($policy->effective_on?->isFuture()) {
            throw ValidationException::withMessages(['policy' => 'Policy is not yet effective.']);
        }
    }

    public function deadline(PolicyRequirement $rule, ActivityVersion $version, PolicyVersion $policy): ?CarbonImmutable
    {
        if ($rule->deadline_status !== 'confirmed') {
            return null;
        }
        $period = $version->activity->academicPeriod;
        if (self::isCmo($policy) && $rule->stage === 'annual_calendar' &&
            in_array($rule->section_row, [4, 10], true) && $period->starts_on->format('Y') === '2026' && $period->ends_on->format('Y') === '2027') {
            return $policy->publication_on ? CarbonImmutable::parse($policy->publication_on->toDateString(), config('lakbay.timezone'))
                ->addDays((int) ($policy->parameters['transition_days_after_publication'] ?? 30))->endOfDay()->utc() : null;
        }
        $anchor = $rule->deadline_anchor === 'academic_year_start' ? (self::isCmo($policy) ? CarbonImmutable::parse($period->starts_on->toDateString(), config('lakbay.timezone')) : $period->starts_on) : $version->starts_at;

        return app(DeadlineCalculator::class)->calculate($rule->deadline_type, $rule->deadline_amount, $anchor,
            config('lakbay.holiday_calendar_version'), $version->activity->campus?->locality);
    }

    public function validateEvidence(PolicyRequirement $rule, int $institutionId, array $ids): void
    {
        $primary = $rule->requirementDefinition->document_type_id;
        $codes = $rule->supporting_document_codes ?? [];
        $found = [];
        $hasPrimary = false;
        foreach ($ids as $id) {
            $doc = Document::with('documentType')->findOrFail($id);
            abort_unless($doc->institution_id === $institutionId, 403);
            $isPrimary = $doc->document_type_id === $primary;
            $signed = ($isPrimary && $rule->signature_mode === 'signed') || $doc->documentType->code === 'certification';
            if ($doc->scan_status !== 'clean' || ($signed && (! $doc->is_signed || $doc->mime !== 'application/pdf')) ||
                (! $isPrimary && ! in_array($doc->documentType->code, $codes, true))) {
                throw ValidationException::withMessages(['documents' => 'Use clean evidence of the required type; official signed forms must be PDF.']);
            }
            $hasPrimary = $hasPrimary || $isPrimary;
            $found[] = $doc->documentType->code;
        }
        if (! $hasPrimary || array_diff($codes, $found)) {
            throw ValidationException::withMessages(['documents' => 'Attach the primary form and all listed supporting evidence: '.implode(', ', $codes)]);
        }
    }
}
