<?php

namespace Database\Seeders;

use App\Actions\Activities\SaveActivity;
use App\Actions\Activities\SaveCertificationDraft;
use App\Actions\Activities\SaveClocaCalendar;
use App\Actions\Activities\SaveSaaDraft;
use App\Actions\Reports\SaveChangeReportDraft;
use App\Actions\Reports\SaveCompletionReportDraft;
use App\Actions\Reports\SaveIncidentReportDraft;
use App\Actions\Submissions\SubmitCalendar;
use App\Models\AcademicPeriod;
use App\Models\AccountabilityAffidavit;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\CertificationDraft;
use App\Models\ChangeReportDraft;
use App\Models\CompletionReportDraft;
use App\Models\Document;
use App\Models\IncidentReportDraft;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Submission;
use App\Models\User;
use App\Services\Compliance\PolicyRules;
use App\Services\Compliance\RequirementResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class QaCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        QaDemoSeeder::assertIsolated();
        config(['lakbay.allow_draft_policy' => true]);
        $manifestPath = storage_path('app/qa-campaign/manifest.json');
        File::ensureDirectoryExists(dirname($manifestPath));
        $manifest = File::exists($manifestPath) ? json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR) : ['accounts' => [], 'records' => []];
        $institution = Institution::where('ched_code', 'QA-SUC')->firstOrFail();
        foreach (['hei' => 'hei_submitter', 'preparer' => 'hei_preparer', 'reviewer' => 'ched_reviewer', 'monitor' => 'ched_monitor',
            'director' => 'ched_signatory', 'records' => 'ched_records', 'analyst' => 'ched_analyst', 'case' => 'ched_case_officer', 'admin' => 'system_administrator', 'other_hei' => 'hei_submitter'] as $name => $role) {
            $email = 'campaign.'.$name.'@example.test';
            $user = User::where('email', $email)->first();
            if (! $user) {
                $password = Str::password(24);
                $user = User::create(['name' => 'Fictional QA '.$name, 'email' => $email, 'password' => $password]);
                $user->forceFill(['email_verified_at' => now(), 'region_id' => str_starts_with($role, 'hei_') ? null : $institution->region_id])->save();
                $user->assignRole($role);
                if (str_starts_with($role, 'hei_')) {
                    $memberInstitution = $name === 'other_hei' ? Institution::where('ched_code', 'QA-PRIVATE')->firstOrFail() : $institution;
                    $user->institutions()->attach($memberInstitution->id, ['verified_at' => now(), 'is_authorized' => $role === 'hei_submitter', 'position' => 'Fictional authorized representative']);
                }
                $manifest['accounts'][$name] = ['email' => $email, 'password' => $password, 'panel' => str_starts_with($role, 'hei_') ? 'hei' : 'ched'];
                File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            }
        }
        $hei = User::where('email', 'campaign.hei@example.test')->firstOrFail();
        $period = AcademicPeriod::firstOrCreate(['institution_id' => $institution->id, 'label' => 'QA-CAMPAIGN-20261008 AY 2026-2027'],
            ['term' => 'First semester', 'starts_on' => '2026-09-01', 'ends_on' => '2027-08-31']);
        $signatories = ['prepared_by' => ['name' => 'Fictional Ana Santos', 'position' => 'OSAS Coordinator', 'contact' => '09170000000', 'email' => 'ana@example.test'],
            'reviewed_by' => ['name' => 'Fictional Luis Reyes', 'position' => 'Dean'], 'noted_by' => ['name' => 'Fictional Maria Cruz', 'position' => 'President']];
        $calendar = Calendar::where('institution_id', $institution->id)->where('title', 'QA-CAMPAIGN-20261008 CLOCA')->first();
        if (! $calendar) {
            $calendar = app(SaveClocaCalendar::class)->execute($hei, ['institution_id' => $institution->id, 'academic_period_id' => $period->id,
                'title' => 'QA-CAMPAIGN-20261008 CLOCA', 'responsible_office' => 'Office of Student Affairs', 'effective_on' => '2026-09-01',
                'annex_b' => ['academic_year' => '2026-2027', 'semester' => 'First semester', 'prepared_by' => $this->printed($signatories['prepared_by']),
                    'reviewed_by' => $this->printed($signatories['reviewed_by']), 'noted_by' => $this->printed($signatories['noted_by'])]]);
        }
        $answers = [];
        foreach (config('diagnostic.groups') as $criteria) {
            foreach ($criteria as $code => $question) {
                $answers[$code] = ['answer' => 'yes', 'remarks' => 'Fictional consultation and institutional evidence reviewed.'];
            }
        }
        $risks = [];
        foreach (config('racp.areas') as $code => $label) {
            $risks[$code] = ['level' => 'low', 'measure' => 'Confirm emergency contacts, inspect transport and brief all participants.'];
        }
        $contacts = [];
        foreach (config('racp.offices') as $code => $label) {
            $contacts[$code] = ['person' => 'Fictional '.$label.' Focal', 'details' => '09170000000'];
        }
        $activity = Activity::where('institution_id', $institution->id)->where('title', 'QA-CAMPAIGN-20261008 Museum learning visit')->first();
        if (! $activity) {
            $activity = app(SaveActivity::class)->execute($hei, ['institution_id' => $institution->id, 'academic_period_id' => $period->id, 'calendar_id' => $calendar->id,
                'title' => 'QA-CAMPAIGN-20261008 Museum learning visit', 'category' => 1, 'academic_nature' => 'curricular', 'activity_type' => 'Educational visit', 'initiator' => 'hei',
                'description' => 'Fictional heritage learning visit in Laguna.', 'learning_objectives' => 'Relate museum exhibits to Philippine history coursework.',
                'responsible_office' => 'Office of Student Affairs', 'starts_at' => '2026-10-01 08:00', 'ends_at' => '2026-10-01 17:00', 'venue' => 'Fictional Laguna Heritage Museum',
                'student_count' => 30, 'other_count' => 1, 'conditions' => ['requires_travel' => true, 'requires_physical_activity' => false], 'authorization' => ['official' => 'Fictional Maria Cruz', 'date' => '2026-09-01'],
                'personnel' => [['role' => 'pic', 'name' => 'Fictional Faculty PIC', 'position' => 'Faculty', 'contact' => 'pic@example.test', 'hei_employed' => true,
                    'accompanies_activity' => true, 'qualifications' => 'Licensed faculty and trained first aider', 'first_aid_trained' => true, 'first_aid_reference' => 'FICTIONAL-FA-001']],
                'cloca' => ['activity_type_code' => 'C', 'duration_days' => 1, 'locations' => [['name' => 'Laguna', 'region_scope' => 'within']], 'agency_coordination' => 'Fictional LGU correspondence', 'requested_assistance' => 'Traffic coordination'],
                'diagnostic' => ['answers' => $answers, 'recommendation' => 'proceed', 'signatories' => $signatories],
                'racp' => ['risks' => $risks, 'contacts' => $contacts, 'other_risks' => [], 'other_contacts' => [], 'signatories' => $signatories],
                'annex_j' => ['signatories' => $signatories],
                'itinerary' => [['starts_at' => '2026-10-01 08:00', 'description' => 'Assembly and safety briefing', 'location' => 'Fictional HEI campus', 'responsible_person' => 'Fictional Faculty PIC'],
                    ['starts_at' => '2026-10-01 10:00', 'description' => 'Guided museum study', 'location' => 'Fictional Laguna Heritage Museum', 'dot_accreditation' => 'N/A - fictional educational venue', 'responsible_person' => 'Fictional Faculty PIC']]]);
        }
        $base = ['institution_id' => $institution->id, 'academic_period_id' => $period->id];
        $saa = AccountabilityAffidavit::where('title', 'QA-CAMPAIGN-20261008 SAA')->first()
            ?? app(SaveSaaDraft::class)->execute($hei, $base + ['title' => 'QA-CAMPAIGN-20261008 SAA', 'details' => ['name' => 'Fictional Maria Cruz', 'position' => 'President', 'citizenship' => 'Filipino']]);
        $items = [];
        foreach (config('certification.rows') as $code => $row) {
            $items[$code] = ['status' => 'attested', 'supporting' => array_fill_keys(array_keys($row['supporting']), true)];
        }
        $certification = CertificationDraft::where('title', 'QA-CAMPAIGN-20261008 Certification')->first()
            ?? app(SaveCertificationDraft::class)->execute($hei, $base + ['title' => 'QA-CAMPAIGN-20261008 Certification', 'details' => ['official' => 'Fictional Maria Cruz', 'position' => 'President', 'items' => $items]]);
        $reportBase = ['institution_id' => $institution->id, 'activity_version_id' => $activity->versions()->orderBy('version')->firstOrFail()->id];
        $roc = CompletionReportDraft::where('title', 'QA-CAMPAIGN-20261008 ROC')->first()
            ?? app(SaveCompletionReportDraft::class)->execute($hei, $reportBase + ['title' => 'QA-CAMPAIGN-20261008 ROC', 'details' => ['ended_at' => '2026-10-01 17:00', 'venue' => 'Fictional Laguna Heritage Museum',
                'student_count' => 30, 'other_count' => 1, 'implementation' => 'as_authorized', 'summary' => 'Students completed guided coursework and group reflections.', 'incidents_encountered' => false,
                'official' => 'Fictional Maria Cruz', 'position' => 'President']]);
        $incident = IncidentReportDraft::where('title', 'QA-CAMPAIGN-20261008 Incident')->first()
            ?? app(SaveIncidentReportDraft::class)->execute($hei, $reportBase + ['title' => 'QA-CAMPAIGN-20261008 Incident', 'details' => ['occurred_at' => '2026-10-01 11:00', 'location' => 'Museum entrance', 'type' => 'Transport delay',
                'description' => 'Fictional transport arrived late; no injuries.', 'immediate_actions' => 'PIC informed the office and adjusted the itinerary.', 'current_status' => 'Resolved.',
                'persons' => [['name' => 'Fictional Faculty PIC', 'role' => 'PIC', 'remarks' => 'Coordinated response']], 'supporting_details' => 'Fictional coordination log.', 'official' => 'Fictional Maria Cruz', 'position' => 'President']]);
        $manifest['records'] = array_replace($manifest['records'], ['institution' => $institution->id, 'calendar' => $calendar->id, 'activity' => $activity->id, 'version' => $reportBase['activity_version_id'], 'saa' => $saa->id, 'certification' => $certification->id, 'roc' => $roc->id, 'incident' => $incident->id]);
        $policy = app(PolicyRules::class)->forDraft(PolicyVersion::where('code', 'CMO24-2026')->firstOrFail());
        if (! array_key_exists('requires_physical_activity', $activity->latestVersion->conditions)) {
            $version = $activity->latestVersion;
            $revision = $version->only(['title', 'category', 'academic_nature', 'activity_type', 'initiator', 'description', 'learning_objectives', 'responsible_office', 'venue', 'student_count', 'other_count', 'conditions', 'authorization', 'cloca']);
            $revision += ['institution_id' => $institution->id, 'academic_period_id' => $period->id, 'calendar_id' => $calendar->id];
            $revision['starts_at'] = $version->starts_at->timezone('Asia/Manila')->format('Y-m-d H:i');
            $revision['ends_at'] = $version->ends_at->timezone('Asia/Manila')->format('Y-m-d H:i');
            $revision['conditions']['requires_physical_activity'] = false;
            $revision['personnel'] = $version->personnel->map(fn ($person) => $person->only(['role', 'name', 'position', 'contact', 'hei_employed', 'accompanies_activity', 'qualifications', 'first_aid_trained', 'first_aid_reference']))->all();
            $activity = app(SaveActivity::class)->execute($hei, $revision, $activity);
        }
        $submission = Submission::where('calendar_id', $calendar->id)->latest('id')->first();
        if (! $submission) {
            $evidence = [];
            $version = $activity->latestVersion;
            foreach (app(RequirementResolver::class)->resolve($version, $policy, 'annual_calendar') as $rule) {
                if ($rule->evidence_mode === 'certified_retained') {
                    $evidence[$version->id][$rule->id] = ['status' => 'certified_retained', 'reason' => 'FICTIONAL certification: signed evidence retained by QA OSAS.'];
                } else {
                    $codes = array_unique([$rule->requirementDefinition->documentType->code, ...($rule->supporting_document_codes ?? [])]);
                    $evidence[$version->id][$rule->id] = ['status' => 'submitted', 'document_ids' => array_map(fn ($code) => $this->document($institution->id, $code)->id, $codes)];
                }
            }
            $submission = app(SubmitCalendar::class)->execute($hei, $calendar, $policy->id, $evidence, saaDraftId: $saa->id, certificationDraftId: $certification->id);
        }
        $manifest['records']['submission'] = $submission->public_id;
        $manifest['records']['submission_id'] = $submission->id;
        $change = ChangeReportDraft::where('title', 'QA-CAMPAIGN-20261008 Change')->first();
        if (! $change) {
            $old = $activity->latestVersion;
            $revision = $old->only(['title', 'category', 'academic_nature', 'activity_type', 'initiator', 'description', 'learning_objectives', 'responsible_office', 'venue', 'student_count', 'other_count', 'conditions', 'authorization']);
            $revision += ['institution_id' => $institution->id, 'academic_period_id' => $period->id, 'calendar_id' => $calendar->id];
            $revision['venue'] = 'Fictional Laguna Alternate Museum';
            $revision['starts_at'] = $old->starts_at->timezone('Asia/Manila')->format('Y-m-d H:i');
            $revision['ends_at'] = $old->ends_at->timezone('Asia/Manila')->format('Y-m-d H:i');
            $revision['personnel'] = $old->personnel->map(fn ($person) => $person->only(['role', 'name', 'position', 'contact', 'hei_employed', 'accompanies_activity', 'qualifications', 'first_aid_trained', 'first_aid_reference']))->all();
            $activity = app(SaveActivity::class)->execute($hei, $revision, $activity);
            $change = app(SaveChangeReportDraft::class)->execute($hei, ['institution_id' => $institution->id,
                'original_version_id' => $old->id, 'revised_version_id' => $activity->latestVersion->id, 'original_submission_id' => $submission->id,
                'title' => 'QA-CAMPAIGN-20261008 Change', 'details' => ['changed_at' => '2026-10-01 07:00', 'reason' => 'Fictional museum venue became unavailable.', 'supporting_details' => 'Alternate venue correspondence.',
                    'prepared_name' => 'Fictional Ana Santos', 'prepared_position' => 'OSAS Coordinator', 'prepared_contact' => '09170000000', 'prepared_email' => 'ana@example.test',
                    'reviewed_name' => 'Fictional Luis Reyes', 'reviewed_position' => 'Dean', 'noted_name' => 'Fictional Maria Cruz', 'noted_position' => 'President']]);
        }
        $manifest['records']['change'] = $change->id;
        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $this->command?->info('Campaign prepared. Random account credentials are in ignored QA storage; MFA enrollment remains required.');
    }

    private function printed(array $signatory): array
    {
        return array_intersect_key($signatory, array_flip(['name', 'position']));
    }

    private function document(int $institutionId, string $code): Document
    {
        return Document::where('institution_id', $institutionId)->whereHas('documentType', fn ($query) => $query->where('code', $code))->where('scan_status', 'clean')->firstOrFail();
    }
}
