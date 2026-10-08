<?php

namespace App\Actions\Submissions;

use App\Jobs\NotifyInstitution;
use App\Models\AccountabilityAffidavit;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\CertificationDraft;
use App\Models\Document;
use App\Models\PolicyVersion;
use App\Models\Submission;
use App\Models\SubmissionActivity;
use App\Models\SubmissionRequirement;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Compliance\ActivityCompliance;
use App\Services\Compliance\PolicyRules;
use App\Services\Compliance\RequirementResolver;
use App\Services\Reporting\CertificationBuilder;
use App\Services\Reporting\ClocaBuilder;
use App\Services\Reporting\DiagnosticBuilder;
use App\Services\Reporting\ItineraryBuilder;
use App\Services\Reporting\RacpBuilder;
use App\Services\Reporting\SaaBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmitActivity
{
    public function execute(User $actor, array $activityIds, int $policyId, array $evidence, ?int $calendarId = null, ?int $previousId = null, ?string $stage = null, ?int $saaDraftId = null, ?int $certificationDraftId = null): Submission
    {
        return DB::transaction(function () use ($actor, $activityIds, $policyId, $evidence, $calendarId, $previousId, $stage, $saaDraftId, $certificationDraftId) {
            if (! $activityIds) {
                throw ValidationException::withMessages(['activities' => 'Select at least one activity.']);
            }
            $activities = Activity::whereIn('id', array_unique($activityIds))->orderBy('id')->lockForUpdate()->get();
            abort_unless($activities->count() === count(array_unique($activityIds)), 404);
            $institutionId = $activities->first()->institution_id;
            app(Access::class)->authorize($actor, $institutionId, 'submissions.submit', true);
            abort_unless($activities->every(fn ($a) => $a->institution_id === $institutionId), 403);
            $policy = PolicyVersion::findOrFail($policyId);
            if (! $previousId && app(PolicyRules::class)->forDraft($policy)->id !== $policy->id) {
                throw ValidationException::withMessages(['policy' => 'A corrected policy version is available. Reopen the submission form and select it to recompute the draft checklist.']);
            }
            app(PolicyRules::class)->authorizeSubmission($policy);
            $cmo = PolicyRules::isCmo($policy);
            $stage ??= $cmo ? ($calendarId ? 'annual_calendar' : 'pre_activity') : 'legacy';
            abort_unless(in_array($stage, $cmo ? ['annual_calendar', 'pre_activity'] : ['legacy'], true), 422);
            if ($cmo && $stage !== ($calendarId ? 'annual_calendar' : 'pre_activity')) {
                throw ValidationException::withMessages(['stage' => 'Submission stage is determined by its calendar or activity context.']);
            }
            $calendar = $calendarId ? Calendar::whereKey($calendarId)->lockForUpdate()->firstOrFail() : null;
            if ($calendar) {
                abort_unless($calendar->institution_id === $institutionId && $activities->every(fn ($a) => $a->calendar_id === $calendarId), 403);
            }
            $previous = $previousId ? Submission::whereKey($previousId)->lockForUpdate()->firstOrFail() : null;
            if ($previous) {
                abort_unless($previous->institution_id === $institutionId && $previous->calendar_id === $calendarId, 403);
                if ($previous->status !== 'corrections_requested' || Submission::where('supersedes_submission_id', $previous->id)->exists()) {
                    throw ValidationException::withMessages(['previous' => 'Only the current correction request can be resubmitted.']);
                }
                if ($previous->policy_version_id !== $policyId || $previous->stage !== $stage) {
                    throw ValidationException::withMessages(['policy' => 'Corrections retain the original policy baseline and submission stage.']);
                }
            }
            $versions = $activities->map(fn ($a) => $a->latestVersion()->firstOrFail());
            if ($previous) {
                $oldIds = $previous->activityVersions()->pluck('activity_versions.activity_id')->sort()->values()->all();
                $newIds = $activities->pluck('id')->sort()->values()->all();
                abort_unless($oldIds === $newIds, 422, 'Correction revisions must cover the original activity set.');
            } elseif (SubmissionActivity::whereIn('activity_version_id', $versions->pluck('id'))->whereHas('submission', fn ($q) => $q->where('stage', $stage)->whereNotIn('status', ['superseded', 'withdrawn']))->exists()) {
                throw ValidationException::withMessages(['submission' => 'This activity revision is already submitted. Use the correction workflow or revise the activity.']);
            }
            if ($calendar && ! $versions->every(fn ($v) => $v->category === 1)) {
                throw ValidationException::withMessages(['category' => 'Annual calendar submissions require Category 1 activities.']);
            }
            if (! $calendar && ! $cmo && $versions->contains(fn ($v) => $v->category === 1)) {
                throw ValidationException::withMessages(['calendar' => 'Submit Category 1 through its calendar.']);
            }
            $rows = [];
            $snapshots = [];
            $documentIds = [];
            $dates = [];
            $resolver = app(RequirementResolver::class);
            foreach ($versions as $v) {
                if ($cmo) {
                    $issues = app(ActivityCompliance::class)->issues($v, $stage === 'pre_activity');
                    if ($issues) {
                        throw ValidationException::withMessages(['readiness' => implode(' ', $issues)]);
                    }
                    if ($stage === 'pre_activity' && $v->category === 1 && ! Submission::where('calendar_id', $v->activity->calendar_id)
                        ->where('institution_id', $institutionId)->where('stage', 'annual_calendar')->whereIn('policy_version_id', app(PolicyRules::class)->compatibleAnnualBaselines($policy))
                        ->whereNotIn('status', ['superseded', 'withdrawn'])->whereHas('activityVersions', fn ($q) => $q->where('activity_versions.activity_id', $v->activity_id))->exists()) {
                        throw ValidationException::withMessages(['calendar' => 'Record the Category 1 annual calendar submission before its pre-activity evidence.']);
                    }
                }
                if (empty($v->authorization['official']) || empty($v->authorization['date'])) {
                    throw ValidationException::withMessages(['authorization' => 'Record HEI authorization before submission.']);
                }
                $period = $v->activity->academicPeriod;
                if ($policy->effective_on && $period->ends_on->lt($policy->effective_on)) {
                    throw ValidationException::withMessages(['policy' => 'Policy does not cover this academic period.']);
                }
                $rules = $resolver->resolve($v, $policy, $cmo ? $stage : null);
                if ($cmo && $stage === 'pre_activity') {
                    $rules = $rules->merge($resolver->resolve($v, $policy, 'requested_evidence'));
                }
                foreach ($rules as $rule) {
                    $item = $evidence[$v->id][$rule->id] ?? [];
                    $status = $item['status'] ?? 'missing';
                    $ids = array_values(array_unique(array_map('intval', $item['document_ids'] ?? [])));
                    if (! in_array($status, ['submitted', 'certified_retained', 'not_applicable'], true)) {
                        throw ValidationException::withMessages(['evidence' => 'Complete all applicable evidence requirements.']);
                    }
                    if ($status !== 'submitted') {
                        $ids = [];
                    }
                    if ($status === 'not_applicable') {
                        if ($rule->predicate === 'always' || empty(trim($item['reason'] ?? ''))) {
                            throw ValidationException::withMessages(['evidence' => 'Not-applicable requires a conditional rule and a reason.']);
                        }
                    } elseif ($status === 'certified_retained') {
                        if ($rule->evidence_mode !== 'certified_retained' || empty(trim($item['reason'] ?? ''))) {
                            throw ValidationException::withMessages(['evidence' => 'Record the certification/retention reference for maintained evidence.']);
                        }
                    } else {
                        if (! $ids) {
                            throw ValidationException::withMessages(['documents' => 'Attach the required signed documents.']);
                        }
                        app(PolicyRules::class)->validateEvidence($rule, $institutionId, $ids);
                    }
                    $due = app(PolicyRules::class)->deadline($rule, $v, $policy);
                    if ($due) {
                        $dates[] = $due;
                    }
                    $documentIds = array_merge($documentIds, $ids);
                    $rows[] = ['activity_version_id' => $v->id, 'policy_requirement_id' => $rule->id, 'evidence_status' => $status, 'reason' => $item['reason'] ?? null, 'document_ids' => $ids, 'due_at' => $due,
                        'rule_snapshot' => $rule->toArray() + ['applicable' => true, 'deadline_resolved' => $due !== null]];
                }
                $snapshots[] = $v->toArray() + ['personnel' => $v->personnel->toArray(), 'itinerary' => $v->itineraryItems->toArray()];
            }
            if (! $rows) {
                throw ValidationException::withMessages(['policy' => 'No applicable requirements configured.']);
            }
            $due = collect($dates)->sort()->first();
            $annexB = $calendar ? app(ClocaBuilder::class)->snapshot($calendar, $versions) : null;
            $snapshot = $annexB ? ['annex_b' => $annexB] : [];
            if ($certificationDraftId) {
                $draft = CertificationDraft::whereKey($certificationDraftId)->lockForUpdate()->firstOrFail();
                abort_unless($draft->institution_id === $institutionId, 403);
                abort_unless($activities->every(fn ($activity) => $activity->academic_period_id === $draft->academic_period_id), 422, 'Choose a certification draft for this package academic period.');
                $builder = app(CertificationBuilder::class);
                $annexF = $builder->snapshot($draft);
                $missing = $builder->missingFields($annexF);
                if ($missing) {
                    throw ValidationException::withMessages(['certification_draft_id' => 'Complete the selected certification draft: '.implode('; ', $missing)]);
                }
                $snapshot['annex_f'] = $annexF;
            } elseif (! empty($previous?->snapshot['annex_f'])) {
                $snapshot['annex_f'] = $previous->snapshot['annex_f'];
            }
            $plans = [];
            $itineraries = [];
            foreach ($versions as $version) {
                $itinerary = app(ItineraryBuilder::class)->snapshot($version);
                if ($itinerary !== null) {
                    $itineraries[$version->id] = $itinerary;
                }
            }
            if ($itineraries) {
                $snapshot['annex_j'] = $itineraries;
            }
            foreach ($versions as $version) {
                $plan = app(RacpBuilder::class)->snapshot($version);
                if ($plan !== null) {
                    $plans[$version->id] = $plan;
                }
            }
            if ($plans) {
                $snapshot['annex_e'] = $plans;
            }
            $diagnostics = [];
            foreach ($versions as $version) {
                $diagnostic = app(DiagnosticBuilder::class)->snapshot($version);
                if ($diagnostic !== null) {
                    $diagnostics[$version->id] = $diagnostic;
                }
            }
            if ($diagnostics) {
                $snapshot['annex_d'] = $diagnostics;
            }
            if ($saaDraftId) {
                $draft = AccountabilityAffidavit::whereKey($saaDraftId)->lockForUpdate()->firstOrFail();
                abort_unless($draft->institution_id === $institutionId, 403);
                abort_unless($activities->every(fn ($activity) => $activity->academic_period_id === $draft->academic_period_id), 422, 'Choose an SAA draft for this package academic period.');
                $builder = app(SaaBuilder::class);
                $annexC = $builder->snapshot($draft);
                $missing = $builder->missingFields($annexC);
                if ($missing) {
                    throw ValidationException::withMessages(['saa_draft_id' => 'Complete the selected SAA draft: '.implode('; ', $missing)]);
                }
                $snapshot['annex_c'] = $annexC;
            } elseif (! empty($previous?->snapshot['annex_c'])) {
                $snapshot['annex_c'] = $previous->snapshot['annex_c'];
            }
            $s = Submission::create(['institution_id' => $institutionId, 'calendar_id' => $calendarId, 'policy_version_id' => $policyId, 'stage' => $stage, 'public_id' => (string) Str::uuid(), 'reference' => 'IV-A-'.now()->format('Y').'-'.Str::upper(Str::random(10)), 'type' => $calendar ? 'calendar' : 'activity', 'revision' => $previous ? $previous->revision + 1 : 1, 'supersedes_submission_id' => $previous?->id, 'submitted_by' => $actor->id, 'submitted_at' => now(), 'received_at' => now(), 'due_at' => $due, 'is_late' => $due !== null && now()->gt($due), 'holiday_calendar_version' => config('lakbay.holiday_calendar_version'), 'snapshot' => $snapshot + ['policy' => $policy->toArray(), 'stage' => $stage, 'activities' => $snapshots, 'evidence' => $rows, 'documents' => Document::whereIn('id', array_unique($documentIds))->get()->toArray()]]);
            foreach ($versions as $v) {
                SubmissionActivity::create(['submission_id' => $s->id, 'activity_version_id' => $v->id]);
            }
            foreach ($rows as $r) {
                SubmissionRequirement::create(['submission_id' => $s->id] + $r);
            }
            if ($previous) {
                $previous->update(['status' => 'superseded']);
            }
            Audit::record($actor, 'submission.received', $s, ['revision' => $s->revision]);
            DB::afterCommit(fn () => NotifyInstitution::dispatch($s->institution_id, 'submission-'.$s->id, 'Submission received', $s->reference.' has been recorded. Review is not activity approval.'));

            return $s;
        });
    }
}
