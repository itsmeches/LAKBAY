<?php

namespace App\Actions\Reports;

use App\Jobs\NotifyChedChangeReport;
use App\Models\Activity;
use App\Models\ActivityVersion;
use App\Models\ChangeReport;
use App\Models\ChangeReportDraft;
use App\Models\ChangeReportItem;
use App\Models\CompletionReport;
use App\Models\CompletionReportDraft;
use App\Models\Document;
use App\Models\IncidentPerson;
use App\Models\IncidentReport;
use App\Models\IncidentReportDraft;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Compliance\DeadlineCalculator;
use App\Services\Compliance\PolicyRules;
use App\Services\Reporting\ChangeReportBuilder;
use App\Services\Reporting\CompletionReportBuilder;
use App\Services\Reporting\IncidentReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmitReport
{
    public function execute(User $actor, string $type, array $data)
    {
        $draft = null;
        $incidentDraft = null;
        $changeDraft = null;
        if ($type === 'change' && ! empty($data['change_report_draft_id'])) {
            Validator::make($data, ['change_report_draft_id' => 'required|integer'])->validate();
            $changeDraft = ChangeReportDraft::findOrFail($data['change_report_draft_id']);
            $access = app(Access::class);
            $access->authorize($actor, $changeDraft->institution_id, 'reports.submit', true);
            abort_unless($actor->can('panel.hei') && $access->owns($actor, $changeDraft->institution_id, true), 403);
            abort_if(isset($data['activity_id']) && (int) $data['activity_id'] !== $changeDraft->activity_id, 403);
            if (! empty($data['document_id'])) {
                $missing = app(ChangeReportBuilder::class)->missingFields(app(ChangeReportBuilder::class)->snapshot($changeDraft));
                if ($missing !== []) {
                    throw ValidationException::withMessages(['change_report_draft_id' => 'Complete the signed Annex I fields: '.implode('; ', $missing)]);
                }
            }
            $data = array_merge($data, $changeDraft->details, ['activity_id' => $changeDraft->activity_id,
                'original_version_id' => $changeDraft->original_version_id, 'revised_version_id' => $changeDraft->revised_version_id,
                'supplements_report_id' => $changeDraft->supplements_report_id]);
        }
        if ($type === 'incident' && ! empty($data['incident_report_draft_id'])) {
            Validator::make($data, ['incident_report_draft_id' => 'required|integer'])->validate();
            $incidentDraft = IncidentReportDraft::findOrFail($data['incident_report_draft_id']);
            app(Access::class)->authorize($actor, $incidentDraft->institution_id, 'reports.submit', true);
            abort_unless($actor->can('panel.hei') && app(Access::class)->owns($actor, $incidentDraft->institution_id, true), 403);
            abort_if(isset($data['activity_id']) && (int) $data['activity_id'] !== $incidentDraft->activity_id, 403);
            if (! empty($data['document_id'])) {
                $missing = app(IncidentReportBuilder::class)->missingFields(app(IncidentReportBuilder::class)->snapshot($incidentDraft));
                if ($missing !== []) {
                    throw ValidationException::withMessages(['incident_report_draft_id' => 'Complete the signed Annex H fields: '.implode('; ', $missing)]);
                }
            }
            $data = array_merge($data, $incidentDraft->details, ['activity_id' => $incidentDraft->activity_id, 'supplements_report_id' => $incidentDraft->supplements_report_id]);
        }
        if ($type === 'completion' && ! empty($data['completion_report_draft_id'])) {
            Validator::make($data, ['completion_report_draft_id' => 'required|integer'])->validate();
            $draft = CompletionReportDraft::findOrFail($data['completion_report_draft_id']);
            app(Access::class)->authorize($actor, $draft->institution_id, 'reports.submit', true);
            abort_if(isset($data['activity_id']) && (int) $data['activity_id'] !== $draft->activity_id, 403);
            $missing = app(CompletionReportBuilder::class)->missingFields(app(CompletionReportBuilder::class)->snapshot($draft));
            if ($missing !== []) {
                throw ValidationException::withMessages(['completion_report_draft_id' => 'Complete the ROC draft: '.implode('; ', $missing)]);
            }
            $data = array_merge($data, $draft->details, ['activity_id' => $draft->activity_id]);
        }
        Validator::make($data, ['activity_id' => 'required|integer'])->validate();
        $activity = Activity::findOrFail($data['activity_id']);
        app(Access::class)->authorize($actor, $activity->institution_id, 'reports.submit', true);
        $eventField = match ($type) {
            'completion' => 'ended_at', 'incident' => 'occurred_at', 'change' => 'changed_at', default => throw new \InvalidArgumentException('Unknown report type.')
        };
        Validator::make($data, [$eventField => 'required|date'])->validate();
        $policy = app(PolicyRules::class)->forActivity($activity, CarbonImmutable::parse($data[$eventField], config('lakbay.timezone'))->utc());
        $cmo = PolicyRules::isCmo($policy);
        $rules = match ($type) {
            'completion' => ['ended_at' => 'required|date', 'venue' => 'required|string', 'student_count' => 'required|integer|min:0', 'implementation' => 'required|in:as_authorized,with_changes', 'summary' => 'required|string', 'incidents_encountered' => 'required|boolean', 'document_id' => 'required|integer', 'change_explanation' => $cmo ? 'required_if:implementation,with_changes|nullable|string' : 'nullable|string', 'incident_report_ids' => 'sometimes|array', 'incident_report_ids.*' => 'integer'],
            'incident' => ['occurred_at' => 'required|date', 'location' => 'required|string', 'type' => 'required|string', 'description' => 'required|string', 'immediate_actions' => 'required|string', 'current_status' => 'required|string', 'document_id' => 'nullable|integer', 'persons' => 'nullable|array', 'persons.*.name' => 'required|string', 'persons.*.role' => 'required|string'],
            'change' => ['original_version_id' => 'required|integer', 'revised_version_id' => 'required|integer|different:original_version_id', 'changed_at' => 'required|date', 'reason' => 'required|string', 'document_id' => $cmo ? 'nullable|integer' : 'required|integer'],
            default => throw new \InvalidArgumentException('Unknown report type.')
        };
        Validator::make($data, $rules)->validate();
        if (! empty($data['document_id'])) {
            $doc = Document::findOrFail($data['document_id']);
            abort_unless($doc->institution_id === $activity->institution_id, 403);
            if ($changeDraft && $doc->confidentiality !== 'institutional') {
                throw ValidationException::withMessages(['document' => 'Use an institutional change-report original; confidential incident evidence belongs in Annex H.']);
            }
            if ($incidentDraft && $doc->confidentiality !== 'sensitive') {
                throw ValidationException::withMessages(['document' => 'Use a sensitive incident-report original to protect persons involved.']);
            }
            if (! $doc->is_signed || $doc->mime !== 'application/pdf' || $doc->scan_status !== 'clean' || $doc->documentType->code !== $type || ! app(Access::class)->document($actor, $doc)) {
                throw ValidationException::withMessages(['document' => 'Use a clean signed report of the correct type.']);
            }
        }
        $anchor = CarbonImmutable::parse($data[match ($type) {
            'completion' => 'ended_at','incident' => 'occurred_at','change' => 'changed_at'
        }], config('lakbay.timezone'))->utc();
        if ($type === 'incident' && $anchor->isFuture()) {
            throw ValidationException::withMessages(['occurred_at' => 'Incident time cannot be in the future (Asia/Manila).']);
        }
        if ($type === 'completion' && $anchor->isFuture()) {
            throw ValidationException::withMessages(['ended_at' => 'Actual conclusion cannot be in the future.']);
        }
        if ($type === 'change' && $anchor->isFuture()) {
            throw ValidationException::withMessages(['changed_at' => 'Change time cannot be in the future (Asia/Manila).']);
        }
        $policy = app(PolicyRules::class)->forActivity($activity, $anchor);
        $parameters = app(PolicyRules::class)->parameters($policy);
        $cmo = PolicyRules::isCmo($policy);
        $postRule = app(PolicyRules::class)->reportRule($policy, $type, $activity->latestVersion->requirement_category);
        $amount = (int) ($postRule?->deadline_amount ?? $parameters[$type.'_hours']);
        $unresolved = $type === 'change' && ($postRule ? $postRule->deadline_status !== 'confirmed' || $postRule->deadline_anchor === 'unresolved' : ($parameters['change_anchor'] ?? '') === 'unresolved');
        $deadlineAnchor = $anchor;
        if ($type === 'change' && $postRule && ! $unresolved) {
            $deadlineAnchor = match ($postRule->deadline_anchor) {
                'actual_conclusion' => $activity->actual_ended_at, 'activity_start' => $activity->actual_started_at, default => null,
            };
            $unresolved = $deadlineAnchor === null;
        }
        $due = $unresolved ? null : app(DeadlineCalculator::class)->calculate('hours_after', $amount, $deadlineAnchor, config('lakbay.holiday_calendar_version'));

        return DB::transaction(function () use ($actor, $type, $data, $activity, $anchor, $due, $rules, $policy, $amount, $unresolved, $cmo, $postRule, $draft, $incidentDraft, $changeDraft) {
            $activity = Activity::whereKey($activity->id)->lockForUpdate()->firstOrFail();
            $values = collect($data)->only(array_keys($rules))->except(['persons', 'incident_report_ids.*'])->all();
            $values[match ($type) {
                'completion' => 'ended_at','incident' => 'occurred_at','change' => 'changed_at'
            }] = $anchor;
            $submission = Submission::whereHas('activityVersions', fn ($q) => $q->where('activity_versions.activity_id', $activity->id))->where('status', '!=', 'superseded')->latest('id')->first();
            $base = ['policy_version_id' => $policy?->id, 'holiday_calendar_version' => config('lakbay.holiday_calendar_version'),
                'deadline_parameters' => ['type' => 'hours_after', 'amount' => $amount, 'anchor' => $unresolved ? 'unresolved' : ($postRule?->deadline_anchor ?? match ($type) {
                    'completion' => 'actual_conclusion', 'incident' => 'incident_occurrence', 'change' => 'changed_at'
                }), 'source_reference' => $postRule?->source_reference,
                    'status' => $unresolved ? 'unresolved' : ($cmo ? 'confirmed_text' : 'legacy_provisional'), 'timezone' => config('lakbay.timezone')],
                'institution_id' => $activity->institution_id, 'activity_id' => $activity->id, 'submitted_by' => $actor->id, 'due_at' => $due, 'is_late' => $due ? now()->gt($due) : null];
            if ($type === 'completion') {
                if ($activity->actual_ended_at && ! $activity->actual_ended_at->equalTo($anchor)) {
                    throw ValidationException::withMessages(['ended_at' => 'Use the previously recorded actual conclusion; report intake cannot overwrite it.']);
                }
                $incidentIds = array_values(array_unique(array_map('intval', $data['incident_report_ids'] ?? [])));
                foreach ($incidentIds as $id) {
                    abort_unless(IncidentReport::findOrFail($id)->activity_id === $activity->id, 403);
                }
                if ($cmo && $data['incidents_encountered'] && ! $incidentIds) {
                    throw ValidationException::withMessages(['incident_report_ids' => 'Link the incident report or initial incident notice.']);
                }
                if ($cmo && ! $data['incidents_encountered'] && IncidentReport::where('activity_id', $activity->id)->exists()) {
                    throw ValidationException::withMessages(['incidents_encountered' => 'Recorded incidents must be disclosed in the completion report.']);
                }
                $values['incident_report_ids'] = $incidentIds;
                if ($draft) {
                    $locked = CompletionReportDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
                    if ($locked->revision !== $draft->revision || $locked->completionReport()->exists()) {
                        throw ValidationException::withMessages(['completion_report_draft_id' => 'The ROC draft changed or has already been filed. Refresh before continuing.']);
                    }
                    $builder = app(CompletionReportBuilder::class);
                    $values['annex_g'] = $builder->snapshot($locked);
                    $builder->letterhead($actor, $values['annex_g']);
                    $values['completion_report_draft_id'] = $locked->id;
                    $values['other_count'] = (int) $locked->details['other_count'];
                    $values['submission_id'] = Submission::whereHas('activityVersions', fn ($q) => $q->where('activity_versions.id', $locked->activity_version_id))
                        ->where('status', '!=', 'superseded')->latest('id')->value('id');
                }
                $report = CompletionReport::create($values + $base);
                $activity->update(['execution_status' => 'completed', 'actual_ended_at' => $anchor]);
            } elseif ($type === 'incident') {
                $original = null;
                if (! empty($data['supplements_report_id'])) {
                    $original = IncidentReport::findOrFail($data['supplements_report_id']);
                    abort_unless($original->activity_id === $activity->id, 403);
                    if (! $anchor->equalTo($original->occurred_at)) {
                        throw ValidationException::withMessages(['occurred_at' => 'A supplement retains the original incident occurrence and deadline.']);
                    }
                    $values['supplements_report_id'] = $original->supplements_report_id ?? $original->id;
                    $base['due_at'] = $original->due_at;
                    $base['policy_version_id'] = $original->policy_version_id;
                    $base['holiday_calendar_version'] = $original->holiday_calendar_version;
                    $base['deadline_parameters'] = $original->deadline_parameters;
                    $base['is_late'] = now()->gt($original->due_at);
                }
                if ($incidentDraft) {
                    $locked = IncidentReportDraft::whereKey($incidentDraft->id)->lockForUpdate()->firstOrFail();
                    if ($locked->revision !== $incidentDraft->revision || $locked->incidentReport()->exists()) {
                        throw ValidationException::withMessages(['incident_report_draft_id' => 'This preparation changed or was already filed. Refresh or prepare a supplement.']);
                    }
                    $builder = app(IncidentReportBuilder::class);
                    $values['annex_h'] = $builder->snapshot($locked);
                    if (! empty($data['document_id'])) {
                        $builder->letterhead($actor, $values['annex_h']);
                        foreach ($locked->details['supporting_document_ids'] ?? [] as $id) {
                            $support = Document::findOrFail($id);
                            abort_unless($support->institution_id === $activity->institution_id && app(Access::class)->document($actor, $support), 403);
                            if ($support->scan_status !== 'clean') {
                                throw ValidationException::withMessages(['document' => 'Selected supporting files are quarantined; file an initial notice without waiting for evidence.']);
                            }
                        }
                    }
                    $values['incident_report_draft_id'] = $locked->id;
                }
                $report = IncidentReport::create($values + $base + ['intake_kind' => empty($data['document_id']) ? 'initial_notice' : 'signed_report', 'provisional_deadline' => $original?->provisional_deadline ?? ! $cmo]);
                foreach ($data['persons'] ?? [] as $p) {
                    IncidentPerson::create(['incident_report_id' => $report->id] + collect($p)->only(['name', 'role', 'remarks'])->all());
                }
            } else {
                $old = ActivityVersion::findOrFail($data['original_version_id']);
                $new = ActivityVersion::findOrFail($data['revised_version_id']);
                abort_unless($old->activity_id === $activity->id && $new->activity_id === $activity->id && $new->version > $old->version, 403);
                $values['intake_kind'] = empty($data['document_id']) ? 'immediate_notice' : 'signed_report';
                $values['notified_at'] = now();
                $originalSubmission = Submission::whereHas('activityVersions', fn ($q) => $q->where('activity_versions.id', $old->id))->oldest('id')->first();
                $values['original_submission_reference'] = $originalSubmission?->reference;
                if ($changeDraft) {
                    $locked = ChangeReportDraft::whereKey($changeDraft->id)->lockForUpdate()->firstOrFail();
                    if ($locked->revision !== $changeDraft->revision || $locked->changeReport()->exists()) {
                        throw ValidationException::withMessages(['change_report_draft_id' => 'This preparation changed or was already filed. Refresh or prepare a follow-up.']);
                    }
                    $builder = app(ChangeReportBuilder::class);
                    $values['annex_i'] = $builder->snapshot($locked);
                    $values['change_report_draft_id'] = $locked->id;
                    $values['original_submission_reference'] = $values['annex_i']['original_submission']['reference'] ?? null;
                    if ($locked->supplements_report_id) {
                        $original = ChangeReport::findOrFail($locked->supplements_report_id);
                        abort_unless($original->activity_id === $activity->id && $original->original_version_id === $old->id && $original->revised_version_id === $new->id, 403);
                        if (! $original->changed_at->equalTo($anchor)) {
                            throw ValidationException::withMessages(['changed_at' => 'A follow-up retains the original change time.']);
                        }
                        if (! empty($original->annex_i['original_submission']) && $original->annex_i['original_submission'] !== $values['annex_i']['original_submission']) {
                            throw ValidationException::withMessages(['change_report_draft_id' => 'Retain the original submission reference on a follow-up.']);
                        }
                        $values['supplements_report_id'] = $original->supplements_report_id ?? $original->id;
                        $values['notified_at'] = $original->notified_at;
                        foreach (['policy_version_id', 'holiday_calendar_version', 'deadline_parameters', 'due_at'] as $key) {
                            $base[$key] = $original->$key;
                        }
                        $base['is_late'] = $original->due_at ? now()->gt($original->due_at) : null;
                    }
                    if (! empty($data['document_id'])) {
                        $builder->letterhead($actor, $values['annex_i']);
                        foreach ($locked->details['supporting_document_ids'] ?? [] as $id) {
                            $support = Document::findOrFail($id);
                            abort_unless($support->institution_id === $activity->institution_id && $support->confidentiality === 'institutional' && app(Access::class)->document($actor, $support), 403);
                            if ($support->scan_status !== 'clean') {
                                throw ValidationException::withMessages(['document' => 'Selected support is quarantined. Send immediate notice without waiting for evidence.']);
                            }
                        }
                    }
                }
                $report = ChangeReport::create($values + $base);
                foreach (['starts_at', 'ends_at', 'venue', 'student_count', 'other_count', 'title', 'description', 'category', 'requirement_category'] as $key) {
                    if ((string) $old->$key !== (string) $new->$key) {
                        ChangeReportItem::create(['change_report_id' => $report->id, 'field' => $key, 'previous_value' => (string) $old->$key, 'revised_value' => (string) $new->$key]);
                    }
                }
                foreach (['personnel', 'itineraryItems'] as $relation) {
                    $previous = $old->$relation->map(fn ($item) => collect($item->toArray())->except(['id', 'activity_version_id', 'created_at', 'updated_at'])->all())->all();
                    $revised = $new->$relation->map(fn ($item) => collect($item->toArray())->except(['id', 'activity_version_id', 'created_at', 'updated_at'])->all())->all();
                    if ($previous !== $revised) {
                        ChangeReportItem::create(['change_report_id' => $report->id, 'field' => $relation, 'previous_value' => json_encode($previous, JSON_THROW_ON_ERROR), 'revised_value' => json_encode($revised, JSON_THROW_ON_ERROR)]);
                    }
                }
            }
            Audit::record($actor, 'report.'.$type.'.received', $report, ['is_late' => $report->is_late]);
            if ($type === 'change') {
                DB::afterCommit(fn () => NotifyChedChangeReport::dispatch($report->id));
            }

            return $report;
        });
    }
}
