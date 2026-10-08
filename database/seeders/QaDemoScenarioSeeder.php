<?php

namespace Database\Seeders;

use App\Actions\Operations\RecordOperationalRecord;
use App\Actions\Reports\SubmitReport;
use App\Actions\Submissions\IssueAcknowledgment;
use App\Actions\Submissions\ReviewSubmission;
use App\Actions\Submissions\SubmitActivity;
use App\Actions\Submissions\SubmitCalendar;
use App\Models\Activity;
use App\Models\CaseRecord;
use App\Models\CompletionReport;
use App\Models\Document;
use App\Models\IncidentReport;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Submission;
use App\Models\User;
use App\Services\Compliance\PolicyRules;
use App\Services\Compliance\RequirementResolver;
use Illuminate\Database\Seeder;

class QaDemoScenarioSeeder extends Seeder
{
    public function run(): void
    {
        QaDemoSeeder::assertIsolated();
        $institution = Institution::where('ched_code', 'QA-SUC')->firstOrFail();
        if (Document::where('institution_id', $institution->id)->where('scan_status', '!=', 'clean')->exists()) {
            throw new \RuntimeException('Run the QA worker and resolve quarantined files first. Scenarios never bypass malware scanning.');
        }
        config(['lakbay.allow_draft_policy' => true]);
        $hei = User::where('email', 'suc.official@example.test')->firstOrFail();
        $reviewer = User::where('email', 'ched.reviewer@example.test')->firstOrFail();
        $director = User::where('email', 'ched.director@example.test')->firstOrFail();
        $policy = app(PolicyRules::class)->forDraft(PolicyVersion::where('code', PolicyRules::CMO_CODE)->firstOrFail());
        foreach (['annual', 'category2', 'completed', 'incident'] as $name) {
            $activity = Activity::where('institution_id', $institution->id)->where('title', 'QA '.$name)->firstOrFail();
            $submission = Submission::whereHas('activityVersions', fn ($query) => $query->where('activity_versions.activity_id', $activity->id))->first();
            if (! $submission) {
                $evidence = $this->evidence($activity, $policy);
                $submission = $name === 'annual'
                    ? app(SubmitCalendar::class)->execute($hei, $activity->calendar, $policy->id, $evidence)
                    : app(SubmitActivity::class)->execute($hei, [$activity->id], $policy->id, $evidence);
            }
            $submission->refresh();
            if ($name === 'category2' && $submission->status === 'submitted') {
                app(ReviewSubmission::class)->execute($reviewer, $submission, 'corrections_requested', 'QA: clarify the venue and PIC contact; submit a new revision.');
            }
            if ($name === 'completed') {
                if ($submission->status === 'submitted') {
                    app(ReviewSubmission::class)->execute($reviewer, $submission, 'complete', 'QA: documents reviewed complete.');
                }
                $submission->refresh();
                if ($submission->status === 'ready_for_acknowledgment') {
                    app(IssueAcknowledgment::class)->execute($director, $submission, [
                        'document_id' => $this->document('acknowledgment')->id, 'reference' => 'QA-ACK-001',
                        'recipient' => 'QA HEI Head', 'signatory' => 'QA Director', 'letter_date' => now('Asia/Manila')->toDateString(),
                    ]);
                }
                if (! CompletionReport::where('activity_id', $activity->id)->exists()) {
                    app(SubmitReport::class)->execute($hei, 'completion', [
                        'activity_id' => $activity->id, 'ended_at' => $activity->latestVersion->ends_at,
                        'venue' => 'QA Learning Venue', 'student_count' => 20, 'implementation' => 'as_authorized',
                        'summary' => 'QA visit concluded safely.', 'incidents_encountered' => false, 'document_id' => $this->document('completion')->id,
                    ]);
                }
            }
            if ($name === 'incident' && ! IncidentReport::where('activity_id', $activity->id)->exists()) {
                app(SubmitReport::class)->execute($hei, 'incident', [
                    'activity_id' => $activity->id, 'occurred_at' => now('Asia/Manila')->subHours(49), 'location' => 'QA Learning Venue',
                    'type' => 'QA transport delay', 'description' => 'Fictional delayed departure.', 'immediate_actions' => 'QA PIC informed HEI.',
                    'current_status' => 'Resolved; simulation only.',
                ]);
            }
        }
        if (! CaseRecord::where('reference', 'QA-CASE-001')->exists()) {
            app(RecordOperationalRecord::class)->execute(User::where('email', 'ched.case@example.test')->firstOrFail(), 'case', [
                'institution_id' => $institution->id, 'reference' => 'QA-CASE-001', 'type' => 'investigation',
                'summary' => 'FICTIONAL ongoing off-campus investigation for clearance testing.', 'status' => 'investigating',
                'opened_at' => now()->subDay(), 'source_document_id' => $this->document('official_order')->id,
            ]);
        }
        $this->command?->info('QA states prepared: late annual intake, correction request, acknowledged/completed activity, late incident, restricted investigation.');
    }

    private function document(string $code): Document
    {
        return Document::where('institution_id', Institution::where('ched_code', 'QA-SUC')->value('id'))->where('original_name', 'qa-'.$code.'.pdf')->where('scan_status', 'clean')->firstOrFail();
    }

    private function evidence(Activity $activity, PolicyVersion $policy): array
    {
        $version = $activity->latestVersion;
        $stage = $version->category === 1 ? 'annual_calendar' : 'pre_activity';
        $resolver = app(RequirementResolver::class);
        $rules = $resolver->resolve($version, $policy, $stage);
        if ($stage === 'pre_activity') {
            $rules = $rules->merge($resolver->resolve($version, $policy, 'requested_evidence'));
        }
        $evidence = [];
        foreach ($rules as $rule) {
            if ($rule->evidence_mode === 'certified_retained') {
                $evidence[$version->id][$rule->id] = ['status' => 'certified_retained', 'reason' => 'QA-CERT-001: signed evidence retained at QA OSAS'];
            } else {
                $documents = [$this->document($rule->requirementDefinition->documentType->code)->id];
                foreach ($rule->supporting_document_codes ?? [] as $code) {
                    $documents[] = $this->document($code)->id;
                }
                $evidence[$version->id][$rule->id] = ['status' => 'submitted', 'document_ids' => $documents];
            }
        }

        return $evidence;
    }
}
