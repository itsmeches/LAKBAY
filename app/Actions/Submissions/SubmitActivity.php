<?php

namespace App\Actions\Submissions;

use App\Jobs\NotifyInstitution;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\PolicyVersion;
use App\Models\Submission;
use App\Models\SubmissionActivity;
use App\Models\SubmissionRequirement;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Compliance\DeadlineCalculator;
use App\Services\Compliance\RequirementResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmitActivity
{
    public function execute(User $actor, array $activityIds, int $policyId, array $evidence, ?int $calendarId = null, ?int $previousId = null): Submission
    {
        return DB::transaction(function () use ($actor, $activityIds, $policyId, $evidence, $calendarId, $previousId) {
            if (! $activityIds) {
                throw ValidationException::withMessages(['activities' => 'Select at least one activity.']);
            }
            $activities = Activity::whereIn('id', array_unique($activityIds))->orderBy('id')->lockForUpdate()->get();
            abort_unless($activities->count() === count(array_unique($activityIds)), 404);
            $institutionId = $activities->first()->institution_id;
            app(Access::class)->authorize($actor, $institutionId, 'submissions.submit', true);
            abort_unless($activities->every(fn ($a) => $a->institution_id === $institutionId), 403);
            $policy = PolicyVersion::findOrFail($policyId);
            if ($policy->status !== 'confirmed' && ! (app()->environment(['local', 'testing']) && config('lakbay.allow_draft_policy'))) {
                throw ValidationException::withMessages(['policy' => 'Confirm the policy before official submission. Draft mode is limited to local/testing.']);
            }
            if ($policy->effective_on && $policy->effective_on->isFuture()) {
                throw ValidationException::withMessages(['policy' => 'Policy is not yet effective.']);
            }
            $calendar = $calendarId ? Calendar::findOrFail($calendarId) : null;
            if ($calendar) {
                abort_unless($calendar->institution_id === $institutionId && $activities->every(fn ($a) => $a->calendar_id === $calendarId), 403);
            }
            $previous = $previousId ? Submission::whereKey($previousId)->lockForUpdate()->firstOrFail() : null;
            if ($previous) {
                abort_unless($previous->institution_id === $institutionId && $previous->calendar_id === $calendarId, 403);
                if ($previous->status !== 'corrections_requested' || Submission::where('supersedes_submission_id', $previous->id)->exists()) {
                    throw ValidationException::withMessages(['previous' => 'Only the current correction request can be resubmitted.']);
                }
            }
            $versions = $activities->map(fn ($a) => $a->latestVersion()->firstOrFail());
            if ($previous) {
                $oldIds = $previous->activityVersions()->pluck('activity_versions.activity_id')->sort()->values()->all();
                $newIds = $activities->pluck('id')->sort()->values()->all();
                abort_unless($oldIds === $newIds, 422, 'Correction revisions must cover the original activity set.');
            } elseif (SubmissionActivity::whereIn('activity_version_id', $versions->pluck('id'))->whereHas('submission', fn ($q) => $q->whereNotIn('status', ['superseded', 'withdrawn']))->exists()) {
                throw ValidationException::withMessages(['submission' => 'This activity revision is already submitted. Use the correction workflow or revise the activity.']);
            }
            if ($calendar && ! $versions->every(fn ($v) => $v->category === 1)) {
                throw ValidationException::withMessages(['category' => 'Annual calendar submissions require Category 1 activities.']);
            }
            if (! $calendar && $versions->contains(fn ($v) => $v->category === 1)) {
                throw ValidationException::withMessages(['calendar' => 'Submit Category 1 through its calendar.']);
            }
            $rows = [];
            $snapshots = [];
            $documentIds = [];
            $dates = [];
            $resolver = app(RequirementResolver::class);
            foreach ($versions as $v) {
                if (empty($v->authorization['official']) || empty($v->authorization['date'])) {
                    throw ValidationException::withMessages(['authorization' => 'Record HEI authorization before submission.']);
                }
                $period = $v->activity->academicPeriod;
                if ($policy->effective_on && $period->ends_on->lt($policy->effective_on)) {
                    throw ValidationException::withMessages(['policy' => 'Policy does not cover this academic period.']);
                }
                foreach ($resolver->resolve($v, $policy) as $rule) {
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
                        foreach ($ids as $id) {
                            $doc = Document::findOrFail($id);
                            abort_unless($doc->institution_id === $institutionId, 403);
                            if ($doc->scan_status !== 'clean' || ! $doc->is_signed || $doc->document_type_id !== $rule->requirementDefinition->document_type_id) {
                                throw ValidationException::withMessages(['documents' => 'Use clean, signed documents of the required type.']);
                            }
                        }
                    }
                    $anchor = $rule->deadline_anchor === 'academic_year_start' ? $period->starts_on : $v->starts_at;
                    $due = app(DeadlineCalculator::class)->calculate($rule->deadline_type, $rule->deadline_amount, $anchor, config('lakbay.holiday_calendar_version'), $v->activity->campus?->locality);
                    $dates[] = $due;
                    $documentIds = array_merge($documentIds, $ids);
                    $rows[] = ['activity_version_id' => $v->id, 'policy_requirement_id' => $rule->id, 'evidence_status' => $status, 'reason' => $item['reason'] ?? null, 'document_ids' => $ids, 'due_at' => $due];
                }
                $snapshots[] = $v->toArray() + ['personnel' => $v->personnel->toArray(), 'itinerary' => $v->itineraryItems->toArray()];
            }
            if (! $rows) {
                throw ValidationException::withMessages(['policy' => 'No applicable requirements configured.']);
            }
            $due = collect($dates)->sort()->first();
            $s = Submission::create(['institution_id' => $institutionId, 'calendar_id' => $calendarId, 'policy_version_id' => $policyId, 'public_id' => (string) Str::uuid(), 'reference' => 'IV-A-'.now()->format('Y').'-'.Str::upper(Str::random(10)), 'type' => $calendar ? 'calendar' : 'activity', 'revision' => $previous ? $previous->revision + 1 : 1, 'supersedes_submission_id' => $previous?->id, 'submitted_by' => $actor->id, 'submitted_at' => now(), 'received_at' => now(), 'due_at' => $due, 'is_late' => now()->gt($due), 'holiday_calendar_version' => config('lakbay.holiday_calendar_version'), 'snapshot' => ['policy' => $policy->toArray(), 'activities' => $snapshots, 'evidence' => $rows, 'documents' => Document::whereIn('id', array_unique($documentIds))->get()->toArray()]]);
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
