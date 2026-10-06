<?php

namespace App\Actions\Reports;

use App\Models\Activity;
use App\Models\ActivityVersion;
use App\Models\ChangeReport;
use App\Models\ChangeReportItem;
use App\Models\CompletionReport;
use App\Models\Document;
use App\Models\IncidentPerson;
use App\Models\IncidentReport;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Compliance\DeadlineCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmitReport
{
    public function execute(User $actor, string $type, array $data)
    {
        Validator::make($data, ['activity_id' => 'required|integer'])->validate();
        $activity = Activity::findOrFail($data['activity_id']);
        app(Access::class)->authorize($actor, $activity->institution_id, 'reports.submit', true);
        $rules = match ($type) {
            'completion' => ['ended_at' => 'required|date', 'venue' => 'required|string', 'student_count' => 'required|integer|min:0', 'implementation' => 'required|in:as_authorized,with_changes', 'summary' => 'required|string', 'incidents_encountered' => 'required|boolean', 'document_id' => 'required|integer'],
            'incident' => ['occurred_at' => 'required|date', 'location' => 'required|string', 'type' => 'required|string', 'description' => 'required|string', 'immediate_actions' => 'required|string', 'current_status' => 'required|string', 'document_id' => 'nullable|integer', 'persons' => 'nullable|array', 'persons.*.name' => 'required|string', 'persons.*.role' => 'required|string'],
            'change' => ['original_version_id' => 'required|integer', 'revised_version_id' => 'required|integer|different:original_version_id', 'changed_at' => 'required|date', 'reason' => 'required|string', 'document_id' => 'required|integer'],
            default => throw new \InvalidArgumentException('Unknown report type.')
        };
        Validator::make($data, $rules)->validate();
        if (! empty($data['document_id'])) {
            $doc = Document::findOrFail($data['document_id']);
            abort_unless($doc->institution_id === $activity->institution_id, 403);
            if (! $doc->is_signed || $doc->scan_status !== 'clean' || $doc->documentType->code !== $type) {
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
        $due = app(DeadlineCalculator::class)->calculate('hours_after', match ($type) {
            'completion' => 72,'incident' => config('lakbay.incident_hours'),'change' => config('lakbay.change_hours')
        }, $anchor, config('lakbay.holiday_calendar_version'));

        return DB::transaction(function () use ($actor, $type, $data, $activity, $anchor, $due, $rules) {
            $values = collect($data)->only(array_keys($rules))->except(['persons'])->all();
            $values[match ($type) {
                'completion' => 'ended_at','incident' => 'occurred_at','change' => 'changed_at'
            }] = $anchor;
            $submission = Submission::whereHas('activityVersions', fn ($q) => $q->where('activity_versions.activity_id', $activity->id))->where('status', '!=', 'superseded')->latest('id')->first();
            $base = ['policy_version_id' => $submission?->policy_version_id, 'holiday_calendar_version' => config('lakbay.holiday_calendar_version'), 'deadline_parameters' => ['type' => 'hours_after', 'amount' => match ($type) {
                'completion' => 72, 'incident' => config('lakbay.incident_hours'), 'change' => config('lakbay.change_hours')
            }, 'timezone' => config('lakbay.timezone')], 'institution_id' => $activity->institution_id, 'activity_id' => $activity->id, 'submitted_by' => $actor->id, 'due_at' => $due, 'is_late' => now()->gt($due)];
            if ($type === 'completion') {
                $report = CompletionReport::create($values + $base);
                $activity->update(['execution_status' => 'completed', 'actual_ended_at' => $anchor]);
            } elseif ($type === 'incident') {
                if (! empty($data['supplements_report_id'])) {
                    abort_unless(IncidentReport::findOrFail($data['supplements_report_id'])->activity_id === $activity->id, 403);
                    $values['supplements_report_id'] = $data['supplements_report_id'];
                }
                $report = IncidentReport::create($values + $base + ['provisional_deadline' => ! config('lakbay.incident_deadline_confirmed')]);
                foreach ($data['persons'] ?? [] as $p) {
                    IncidentPerson::create(['incident_report_id' => $report->id] + collect($p)->only(['name', 'role', 'remarks'])->all());
                }
            } else {
                $old = ActivityVersion::findOrFail($data['original_version_id']);
                $new = ActivityVersion::findOrFail($data['revised_version_id']);
                abort_unless($old->activity_id === $activity->id && $new->activity_id === $activity->id && $new->version > $old->version, 403);
                $report = ChangeReport::create($values + $base);
                foreach (['starts_at', 'ends_at', 'venue', 'student_count', 'title', 'description', 'category', 'requirement_category'] as $key) {
                    if ((string) $old->$key !== (string) $new->$key) {
                        ChangeReportItem::create(['change_report_id' => $report->id, 'field' => $key, 'previous_value' => (string) $old->$key, 'revised_value' => (string) $new->$key]);
                    }
                }
            }
            Audit::record($actor, 'report.'.$type.'.received', $report, ['is_late' => $report->is_late]);

            return $report;
        });
    }
}
