<?php

namespace Tests\Feature;

use App\Actions\Activities\SaveActivity;
use App\Actions\Documents\RequestEvidence;
use App\Actions\Documents\UploadDocument;
use App\Actions\Operations\ManageRegionalReport;
use App\Actions\Operations\RecordCaseEvent;
use App\Actions\Operations\RecordClearanceRouting;
use App\Actions\Operations\RecordOperationalRecord;
use App\Actions\Reports\SubmitReport;
use App\Actions\Submissions\ConfirmPolicy;
use App\Actions\Submissions\IssueAcknowledgment;
use App\Actions\Submissions\ReviewSubmission;
use App\Actions\Submissions\SubmitActivity;
use App\Actions\Submissions\SubmitCalendar;
use App\Filament\Hei\Resources\Pages\ListCalendar;
use App\Jobs\GenerateExport;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Export;
use App\Models\FormTemplate;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\WorkflowNotice;
use App\Services\Compliance\ActivityCompliance;
use App\Services\Compliance\ActivityReadiness;
use App\Services\Compliance\PolicyRules;
use App\Services\Compliance\RequirementResolver;
use App\Services\Reporting\RegionalReportBuilder;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Cmo24AlignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Institution $institution;

    private Calendar $calendar;

    private PolicyVersion $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-07 10:00', 'Asia/Manila'));
        $this->seed();
        Queue::fake();
        Storage::fake('private');
        config(['lakbay.allow_draft_policy' => true]);
        $this->policy = PolicyVersion::where('code', PolicyRules::CMO_CODE)->firstOrFail();
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'CMO-TEST', 'name' => 'CMO test HEI', 'institution_type' => 'private']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Submitter', 'is_authorized' => true, 'verified_at' => now()]);
        $period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'AY 2026-2027', 'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->calendar = Calendar::create(['institution_id' => $this->institution->id, 'academic_period_id' => $period->id, 'title' => 'CLOCA', 'responsible_office' => 'OSAS', 'created_by' => $this->hei->id]);
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $user->assignRole($role);

        return $user;
    }

    private function activity(int $category = 2, array $overrides = []): Activity
    {
        return app(SaveActivity::class)->execute($this->hei, array_replace([
            'institution_id' => $this->institution->id, 'academic_period_id' => $this->calendar->academic_period_id,
            'calendar_id' => $category === 1 ? $this->calendar->id : null, 'title' => 'Learning visit', 'category' => $category,
            'academic_nature' => 'non_curricular', 'activity_type' => 'Learning visit', 'initiator' => $category === 3 ? 'student' : 'hei',
            'description' => 'Purpose', 'learning_objectives' => 'Outcomes', 'responsible_office' => 'OSAS', 'program' => 'BS Test',
            'starts_at' => '2026-12-10 09:00', 'ends_at' => '2026-12-10 17:00', 'venue' => 'Venue', 'student_count' => 20,
            'conditions' => ['requires_physical_activity' => false, 'requires_travel' => false],
            'authorization' => ['official' => 'HEI head', 'date' => '2026-10-01'],
            'personnel' => [['role' => 'pic', 'name' => 'PIC', 'position' => 'Faculty', 'contact' => 'test@example.test',
                'qualifications' => 'Relevant experience', 'hei_employed' => true, 'accompanies_activity' => true,
                'first_aid_trained' => true, 'first_aid_reference' => 'TRAIN-1']],
        ], $overrides));
    }

    private function document(string $code, bool $signed = true, string $scan = 'clean'): Document
    {
        $type = DocumentType::where('code', $code)->firstOrFail();
        $path = 'tests/'.Str::uuid().'.pdf';
        Storage::disk('private')->put($path, '%PDF-test');

        return Document::create(['institution_id' => $this->institution->id, 'document_type_id' => $type->id,
            'public_id' => (string) Str::uuid(), 'path' => $path, 'original_name' => $code.'.pdf', 'mime' => 'application/pdf',
            'size' => 9, 'sha256' => hash('sha256', '%PDF-test'), 'confidentiality' => $type->confidentiality,
            'scan_status' => $scan, 'is_signed' => $signed, 'uploaded_by' => $this->hei->id]);
    }

    private function evidence(Activity $activity, string $stage): array
    {
        $version = $activity->latestVersion;
        $rules = app(RequirementResolver::class)->resolve($version, $this->policy, $stage);
        if ($stage === 'pre_activity') {
            $rules = $rules->merge(app(RequirementResolver::class)->resolve($version, $this->policy, 'requested_evidence'));
        }
        $evidence = [];
        $documents = [];
        foreach ($rules as $rule) {
            if ($rule->evidence_mode === 'certified_retained') {
                $evidence[$version->id][$rule->id] = ['status' => 'certified_retained', 'reason' => 'Signed certification CERT-1; evidence retained at OSAS'];

                continue;
            }
            $code = $rule->requirementDefinition->document_type_id;
            $type = DocumentType::findOrFail($code);
            $documents[$type->code] ??= $this->document($type->code, $rule->signature_mode === 'signed');
            $ids = [$documents[$type->code]->id];
            foreach ($rule->supporting_document_codes ?? [] as $support) {
                $documents[$support] ??= $this->document($support, $support === 'certification');
                $ids[] = $documents[$support]->id;
            }
            $evidence[$version->id][$rule->id] = ['status' => 'submitted', 'document_ids' => $ids];
        }

        return $evidence;
    }

    private function submit(Activity $activity): Submission
    {
        if ($activity->latestVersion->category === 1) {
            return app(SubmitCalendar::class)->execute($this->hei, $this->calendar, $this->policy->id, $this->evidence($activity, 'annual_calendar'));
        }

        return app(SubmitActivity::class)->execute($this->hei, [$activity->id], $this->policy->id, $this->evidence($activity, 'pre_activity'));
    }

    public static function categoryRows(): array
    {
        $all = [1, 2, 3];

        return array_map(fn ($row, $categories) => [$row, $categories], range(1, 22),
            [$all, $all, [1], [1, 2], [1, 2], $all, [2, 3], [2, 3], [2, 3], $all, [1, 2], [1, 2], $all, $all, [2, 3], $all, $all, [2, 3], [3], $all, $all, $all]);
    }

    #[DataProvider('categoryRows')]
    public function test_section_14_category_matrix(int $row, array $categories): void
    {
        $actual = $this->policy->requirements()->where('section_row', $row)->where('stage', '!=', 'requested_evidence')->orderBy('category')->pluck('category')->all();
        $this->assertSame($categories, $actual);
    }

    public function test_candidate_seed_is_idempotent_and_preserves_draft_templates(): void
    {
        $before = $this->policy->requirements()->count();
        $this->seed();
        $this->assertSame($before, $this->policy->requirements()->count());
        $this->assertSame('candidate', $this->policy->status);
        $this->assertNull($this->policy->publication_on);
        $this->assertSame('2026-09-28', $this->policy->issued_on->toDateString());
        $this->assertDatabaseHas('form_templates', ['policy_version_id' => $this->policy->id, 'code' => 'consent', 'annex' => 'L', 'source_page' => 32]);
        $this->assertDatabaseHas('form_templates', ['policy_version_id' => $this->policy->id, 'code' => 'monitoring', 'annex' => 'M', 'source_page' => 33]);
        $this->assertDatabaseHas('form_templates', ['code' => 'consent', 'annex' => 'H (duplicate)']);
    }

    public function test_production_cannot_use_candidate_even_with_local_flag(): void
    {
        $activity = $this->activity();
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(ValidationException::class);
        $this->submit($activity);
    }

    public function test_publication_is_required_and_effectivity_is_derived(): void
    {
        $admin = $this->staff('system_administrator');
        try {
            app(ConfirmPolicy::class)->execute($admin, $this->policy, ['confirmed_by_ched' => true, 'confirmation_reference' => 'Test authority']);
            $this->fail('Missing publication accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('publication_on', $e->errors());
        }
        $policy = app(ConfirmPolicy::class)->execute($admin, $this->policy, ['confirmed_by_ched' => true,
            'confirmation_reference' => 'TEST ONLY', 'publication_on' => '2026-10-01', 'publication_reference' => 'TEST Gazette reference']);
        $this->assertSame('2026-10-16', $policy->effective_on->toDateString());
        $this->expectException(ValidationException::class);
        app(PolicyRules::class)->authorizeSubmission($policy);
    }

    public function test_copy_of_cmo_retains_publication_activation_guard(): void
    {
        $copy = $this->policy->replicate();
        $copy->code = 'CMO-REVISION-TEST';
        $copy->save();
        $this->assertTrue(PolicyRules::isCmo($copy));
        $copy->update(['status' => 'confirmed', 'effective_on' => '2026-10-01']);
        $this->expectException(ValidationException::class);
        app(PolicyRules::class)->authorizeSubmission($copy);
    }

    public function test_annual_and_pre_activity_are_separate_immutable_packages(): void
    {
        $activity = $this->activity(1);
        $annual = $this->submit($activity);
        $this->assertSame('annual_calendar', $annual->stage);
        $this->assertFalse($annual->requirements()->whereHas('policyRequirement', fn ($q) => $q->where('section_row', 16))->exists());
        $snapshot = $annual->snapshot;
        $pre = app(SubmitActivity::class)->execute($this->hei, [$activity->id], $this->policy->id, $this->evidence($activity, 'pre_activity'));
        $this->assertSame('pre_activity', $pre->stage);
        $this->assertSame($snapshot, $annual->fresh()->snapshot);
        $this->assertTrue($pre->requirements()->whereHas('policyRequirement', fn ($q) => $q->where('section_row', 16))->exists());
        $this->assertNotEmpty($pre->requirements->first()->rule_snapshot);
        $this->expectException(ValidationException::class);
        app(SubmitActivity::class)->execute($this->hei, [$activity->id], $this->policy->id, $this->evidence($activity, 'pre_activity'));
    }

    public function test_category_one_pre_activity_requires_calendar_intake(): void
    {
        $activity = $this->activity(1);
        $this->expectException(ValidationException::class);
        app(SubmitActivity::class)->execute($this->hei, [$activity->id], $this->policy->id, $this->evidence($activity, 'pre_activity'));
    }

    public function test_activity_submission_cannot_forge_annual_calendar_stage(): void
    {
        $activity = $this->activity(1);
        $this->expectException(ValidationException::class);
        app(SubmitActivity::class)->execute($this->hei, [$activity->id], $this->policy->id, $this->evidence($activity, 'annual_calendar'), null, null, 'annual_calendar');
    }

    public function test_transition_deadline_uses_publication_not_issuance(): void
    {
        $activity = $this->activity(1);
        $row = $this->policy->requirements()->where('section_row', 4)->where('category', 1)->firstOrFail();
        $this->assertNull(app(PolicyRules::class)->deadline($row, $activity->latestVersion, $this->policy));
        $this->policy->update(['publication_on' => '2026-10-01']);
        $due = app(PolicyRules::class)->deadline($row, $activity->latestVersion, $this->policy);
        $this->assertSame('2026-10-31 23:59', $due->timezone('Asia/Manila')->format('Y-m-d H:i'));
    }

    public function test_category_three_saa_is_required_with_unresolved_deadline(): void
    {
        $activity = $this->activity(3);
        $s = $this->submit($activity);
        $saa = $s->requirements()->whereHas('policyRequirement', fn ($q) => $q->where('section_row', 10))->firstOrFail();
        $this->assertNull($saa->due_at);
        $this->assertSame('unresolved', $saa->rule_snapshot['deadline_status']);
        $this->assertSame(3, $s->snapshot['activities'][0]['category']);
    }

    public static function picBoundaries(): array
    {
        return [[30, true, false], [31, true, true], [50, false, false], [51, false, true]];
    }

    #[DataProvider('picBoundaries')]
    public function test_pic_ratio_boundaries(int $students, bool $travel, bool $hasIssue): void
    {
        $activity = $this->activity(2, ['student_count' => $students, 'conditions' => ['requires_physical_activity' => false, 'requires_travel' => $travel]]);
        $issues = app(ActivityCompliance::class)->issues($activity->latestVersion);
        $this->assertSame($hasIssue, count($issues) > 0);
    }

    public function test_apic_does_not_replace_pic_and_missing_training_blocks_submission(): void
    {
        $activity = $this->activity(2, ['student_count' => 31, 'conditions' => ['requires_physical_activity' => true, 'requires_travel' => true],
            'personnel' => [['role' => 'pic', 'name' => 'Unqualified', 'position' => 'Faculty', 'contact' => 'test'], ['role' => 'apic', 'name' => 'APIC', 'position' => 'Faculty', 'contact' => 'test']]]);
        $this->assertCount(2, app(ActivityCompliance::class)->issues($activity->latestVersion));
        $this->expectException(ValidationException::class);
        $this->submit($activity);
    }

    public function test_venue_and_medical_risk_are_distinct_from_accommodation_and_vulnerability(): void
    {
        $resolver = app(RequirementResolver::class);
        $this->assertTrue($resolver->applies('venue_accreditation', ['venue_requires_accreditation' => true]));
        $this->assertFalse($resolver->applies('medical_risk', ['vulnerable_participants' => true]));
        $this->assertTrue($resolver->applies('medical_risk', ['medical_clearance_relevant' => true]));
        $activity = $this->activity(3, ['conditions' => ['vulnerable_participants' => true, 'requires_physical_activity' => false, 'requires_travel' => false]]);
        $s = $this->submit($activity);
        $this->assertSame(3, $s->snapshot['activities'][0]['category']);
        $this->assertSame(2, $s->snapshot['activities'][0]['requirement_category']);
    }

    public function test_unsigned_invitation_is_accepted_but_quarantine_and_unsigned_saa_are_rejected(): void
    {
        $activity = $this->activity(2, ['conditions' => ['external_invitation' => true, 'requires_physical_activity' => false, 'requires_travel' => false]]);
        $rule = $this->policy->requirements()->where('section_row', 7)->where('category', 2)->firstOrFail();
        $copy = $this->document('invitation', false);
        app(PolicyRules::class)->validateEvidence($rule, $this->institution->id, [$copy->id]);
        $this->submit($activity);
        $saa = $this->policy->requirements()->where('section_row', 10)->where('category', 2)->firstOrFail();
        try {
            app(PolicyRules::class)->validateEvidence($saa, $this->institution->id, [$this->document('saa', false)->id]);
            $this->fail('Unsigned SAA accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('documents', $e->errors());
        }
        $copy->update(['scan_status' => 'pending']);
        $this->expectException(ValidationException::class);
        app(PolicyRules::class)->validateEvidence($rule, $this->institution->id, [$copy->id]);
    }

    public function test_required_supporting_evidence_cannot_be_omitted(): void
    {
        $rule = $this->policy->requirements()->where('section_row', 6)->where('category', 2)->firstOrFail();
        $this->expectException(ValidationException::class);
        app(PolicyRules::class)->validateEvidence($rule, $this->institution->id, [$this->document('certification')->id]);
    }

    public static function incidentBoundaries(): array
    {
        return [[47, 59, false], [48, 0, false], [48, 1, true]];
    }

    #[DataProvider('incidentBoundaries')]
    public function test_incident_uses_48_elapsed_hours_and_accepts_initial_notice(int $hours, int $minutes, bool $late): void
    {
        $activity = $this->activity();
        $this->submit($activity);
        $occurred = now()->subHours($hours)->subMinutes($minutes)->timezone('Asia/Manila');
        $report = app(SubmitReport::class)->execute($this->hei, 'incident', ['activity_id' => $activity->id, 'occurred_at' => $occurred->format('Y-m-d H:i:s'),
            'location' => 'Venue', 'type' => 'Incident', 'description' => 'Known facts', 'immediate_actions' => 'Assistance', 'current_status' => 'Reported']);
        $this->assertSame(48, $report->deadline_parameters['amount']);
        $this->assertSame($late, $report->is_late);
        $this->assertSame('initial_notice', $report->intake_kind);
        $this->assertFalse($report->provisional_deadline);
    }

    public function test_changes_preserve_personnel_and_itinerary_differences_without_invented_due_date(): void
    {
        $activity = $this->activity();
        $submission = $this->submit($activity);
        $old = $activity->latestVersion;
        $data = $old->only(['category', 'academic_nature', 'activity_type', 'initiator', 'title', 'description', 'learning_objectives', 'responsible_office', 'starts_at', 'ends_at', 'venue', 'student_count', 'conditions', 'authorization']);
        $data += ['institution_id' => $this->institution->id, 'academic_period_id' => $activity->academic_period_id, 'personnel' => [['role' => 'pic', 'name' => 'New PIC', 'position' => 'Faculty', 'contact' => 'new']]];
        $revised = app(SaveActivity::class)->execute($this->hei, $data, $activity);
        $report = app(SubmitReport::class)->execute($this->hei, 'change', ['activity_id' => $activity->id, 'original_version_id' => $old->id,
            'revised_version_id' => $revised->latestVersion->id, 'changed_at' => '2026-10-07 09:00', 'reason' => 'Personnel change']);
        $this->assertNull($report->due_at);
        $this->assertNull($report->is_late);
        $this->assertSame('immediate_notice', $report->intake_kind);
        $this->assertSame($submission->reference, $report->original_submission_reference);
        $this->assertDatabaseHas('change_report_items', ['change_report_id' => $report->id, 'field' => 'personnel']);
        $this->assertSame('PIC', $old->personnel->first()->name);
    }

    public function test_completion_cannot_overwrite_recorded_actual_conclusion(): void
    {
        $activity = $this->activity();
        $this->submit($activity);
        $activity->update(['actual_ended_at' => now()->subHour()]);
        $this->expectException(ValidationException::class);
        app(SubmitReport::class)->execute($this->hei, 'completion', ['activity_id' => $activity->id, 'ended_at' => '2026-10-07 08:00',
            'venue' => 'Venue', 'student_count' => 20, 'implementation' => 'as_authorized', 'summary' => 'Completed', 'incidents_encountered' => false, 'document_id' => $this->document('completion')->id]);
    }

    public function test_retained_evidence_request_is_immediate_and_requires_signed_assessment(): void
    {
        $submission = $this->submit($this->activity());
        $request = app(RequestEvidence::class)->execute($this->staff('ched_reviewer'), $submission, ['document_type_id' => DocumentType::where('code', 'racp')->value('id'), 'reason' => 'Verification']);
        $this->assertTrue($request->due_at->equalTo(now()));
        $this->assertSame('immediate_on_request', $request->deadline_parameters['type']);
        $this->expectException(HttpException::class);
        app(RequestEvidence::class)->respond($this->hei, $request, $this->document('racp', false)->id);
    }

    public function test_effective_third_party_contract_is_required_for_pre_activity(): void
    {
        $conditions = ['requires_physical_activity' => false, 'requires_travel' => false, 'has_third_party_services' => true];
        $missing = $this->activity(2, ['conditions' => $conditions]);
        $this->assertNotEmpty(app(ActivityCompliance::class)->issues($missing->latestVersion));
        $valid = $this->activity(2, ['conditions' => $conditions, 'contracts' => [['provider' => 'Provider', 'service' => 'Transport',
            'document_id' => $this->document('third_party_contract')->id, 'effective_from' => '2026-12-01', 'effective_until' => '2026-12-31']]]);
        $this->assertSame([], app(ActivityCompliance::class)->issues($valid->latestVersion));
    }

    public function test_clearance_records_90_day_filing_and_authorized_routing(): void
    {
        $activity = $this->activity();
        $this->submit($activity);
        $officer = $this->staff('ched_case_officer');
        $case = app(RecordOperationalRecord::class)->execute($officer, 'case', ['institution_id' => $this->institution->id,
            'reference' => 'INV-TEST', 'type' => 'investigation', 'status' => 'investigating', 'summary' => 'Authoritative investigation',
            'opened_at' => '2026-10-01', 'source_document_id' => $this->document('official_order')->id]);
        $request = app(RecordOperationalRecord::class)->execute($officer, 'clearance', ['institution_id' => $this->institution->id,
            'activity_version_id' => $activity->latestVersion->id, 'case_record_id' => $case->id, 'justification' => 'Grounds',
            'application_received_at' => '2026-10-07 09:00', 'application_document_id' => $this->document('clearance_application')->id,
            'application_due_at' => '2099-01-01', 'application_is_late' => false]);
        $this->assertSame('2026-09-11', $request->application_due_at->timezone('Asia/Manila')->toDateString());
        $this->assertTrue($request->application_is_late);
        $this->assertNull($request->decision);
        app(RecordClearanceRouting::class)->execute($officer, $request, ['stage' => 'chedro_endorsement', 'reference' => 'END-1',
            'occurred_at' => '2026-10-07 09:30', 'document_id' => $this->document('clearance_correspondence', false)->id]);
        $this->assertCount(1, $request->fresh()->routing_history);
        $this->expectException(HttpException::class);
        app(RecordClearanceRouting::class)->execute($this->staff('system_administrator'), $request, []);
    }

    public function test_complaint_does_not_create_investigation_or_suspension(): void
    {
        $officer = $this->staff('ched_case_officer');
        $case = app(RecordOperationalRecord::class)->execute($officer, 'case', ['institution_id' => $this->institution->id,
            'reference' => 'COMPLAINT-1', 'type' => 'complaint', 'status' => 'received', 'summary' => 'Written complaint',
            'opened_at' => '2026-10-07', 'source_document_id' => $this->document('complaint')->id]);
        $this->assertDatabaseCount('institutional_restrictions', 0);
        $this->expectException(HttpException::class);
        app(RecordCaseEvent::class)->execute($officer, $case, 'investigating', 'Not authoritative', $this->document('official_order')->id);
    }

    public function test_annual_snapshot_remains_frozen_through_dispatch_and_receipt(): void
    {
        $this->submit($this->activity());
        $analyst = $this->staff('ched_analyst');
        $report = app(ManageRegionalReport::class)->prepare($analyst, ['period' => '2026 monitoring', 'period_from' => '2026-01-01 00:00', 'cutoff_at' => '2026-10-07 10:00', 'report_type' => 'annual_monitoring', 'reporting_year' => 2026]);
        $snapshot = $report->filters;
        $this->assertNull($report->due_at);
        $this->assertStringContainsString('Last week of August', $report->due_window);
        app(ManageRegionalReport::class)->dispatch($analyst, $report, ['sent_at' => '2026-10-07 09:00', 'reference' => 'DISPATCH-1', 'document_id' => $this->document('monitoring')->id]);
        app(ManageRegionalReport::class)->receive($analyst, $report->fresh(), ['received_at' => '2026-10-07 09:30', 'reference' => 'OSDS-1']);
        $this->assertSame($snapshot, $report->fresh()->filters);
        $this->assertSame('OSDS-1', $report->fresh()->receipt_reference);
        $this->expectException(\LogicException::class);
        $report->fresh()->update(['totals' => []]);
    }

    public function test_acknowledgment_summarizes_programs_and_does_not_authorize_execution(): void
    {
        $activity = $this->activity();
        $submission = $this->submit($activity);
        app(ReviewSubmission::class)->execute($this->staff('ched_reviewer'), $submission, 'complete', 'Complete');
        $ack = app(IssueAcknowledgment::class)->execute($this->staff('ched_signatory'), $submission, ['document_id' => $this->document('acknowledgment')->id,
            'reference' => 'ACK-CMO', 'recipient' => 'HEI', 'signatory' => 'Director', 'letter_date' => '2026-10-07']);
        $this->assertSame('BS Test', $ack->summary['programs'][0]['program_course']);
        $this->assertSame(20, $ack->summary['programs'][0]['student_participations']);
        $this->assertSame('planned', $activity->fresh()->execution_status);
    }

    public function test_cmo_calendar_action_works_in_filament(): void
    {
        $activity = $this->activity(1);
        $evidence = $this->evidence($activity, 'annual_calendar')[$activity->latestVersion->id];
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        Livewire::test(ListCalendar::class)->callAction(TestAction::make('submit')->table($this->calendar),
            data: ['policy_id' => $this->policy->id, 'evidence' => $evidence])->assertHasNoActionErrors();
        $this->assertDatabaseHas('submissions', ['policy_version_id' => $this->policy->id, 'stage' => 'annual_calendar']);
    }

    public function test_correction_cannot_switch_policy_baseline(): void
    {
        $activity = $this->activity();
        $submission = $this->submit($activity);
        app(ReviewSubmission::class)->execute($this->staff('ched_reviewer'), $submission, 'corrections_requested', 'Correct');
        $legacy = PolicyVersion::where('code', 'LAKBAY-JULY-2026-DRAFT')->firstOrFail();
        $this->expectException(ValidationException::class);
        app(SubmitActivity::class)->execute($this->hei, [$activity->id], $legacy->id, [], null, $submission->id);
    }

    public function test_other_institution_cannot_submit_or_link_evidence(): void
    {
        $activity = $this->activity();
        $other = User::factory()->create(['app_authentication_secret' => 'SECRET']);
        $other->assignRole('hei_submitter');
        $this->expectException(HttpException::class);
        app(SubmitActivity::class)->execute($other, [$activity->id], $this->policy->id, $this->evidence($activity, 'pre_activity'));
    }

    public function test_incident_supplement_preserves_occurrence_deadline_and_root_count(): void
    {
        $activity = $this->activity();
        $this->submit($activity);
        $data = ['activity_id' => $activity->id, 'occurred_at' => '2026-10-07 09:00', 'location' => 'Venue', 'type' => 'Incident',
            'description' => 'Facts', 'immediate_actions' => 'Assistance', 'current_status' => 'Reported'];
        $initial = app(SubmitReport::class)->execute($this->hei, 'incident', $data);
        $this->travel(3)->days();
        $supplement = app(SubmitReport::class)->execute($this->hei, 'incident', $data + ['supplements_report_id' => $initial->id, 'document_id' => $this->document('incident')->id]);
        $this->assertTrue($supplement->due_at->equalTo($initial->due_at));
        $this->assertTrue($supplement->is_late);
        $this->assertSame('signed_report', $supplement->intake_kind);
        $this->assertSame(1, app(RegionalReportBuilder::class)->build($this->staff('ched_analyst'))['totals']['incidents']);
        $this->expectException(ValidationException::class);
        app(SubmitReport::class)->execute($this->hei, 'incident', array_replace($data, ['occurred_at' => now()->timezone('Asia/Manila')->format('Y-m-d H:i'), 'supplements_report_id' => $initial->id]));
    }

    public function test_candidate_report_rules_and_historical_snapshots_are_versioned(): void
    {
        $activity = $this->activity();
        $submission = $this->submit($activity);
        $snapshot = $submission->snapshot;
        $row = $this->policy->requirements()->where('section_row', 21)->where('category', 2)->firstOrFail();
        $row->update(['deadline_amount' => 49, 'source_reference' => 'TEST proposed revision — not official policy']);
        $report = app(SubmitReport::class)->execute($this->hei, 'incident', ['activity_id' => $activity->id, 'occurred_at' => '2026-10-07 09:00',
            'location' => 'Venue', 'type' => 'Incident', 'description' => 'Facts', 'immediate_actions' => 'Assistance', 'current_status' => 'Reported']);
        $this->assertSame(49, $report->deadline_parameters['amount']);
        $this->assertSame($snapshot, $submission->fresh()->snapshot);
        $row->update(['deadline_amount' => 50]);
        $this->assertSame(49, $report->fresh()->deadline_parameters['amount']);
    }

    public function test_annual_report_requires_period_and_filters_activity_dates(): void
    {
        $this->submit($this->activity());
        $analyst = $this->staff('ched_analyst');
        try {
            app(ManageRegionalReport::class)->prepare($analyst, ['period' => 'Annual', 'cutoff_at' => '2026-10-07 10:00', 'report_type' => 'annual_monitoring', 'reporting_year' => 2026]);
            $this->fail('Missing annual period accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('period_from', $e->errors());
        }
        $report = app(ManageRegionalReport::class)->prepare($analyst, ['period' => 'Annual', 'period_from' => '2026-01-01 00:00', 'cutoff_at' => '2026-10-07 10:00', 'report_type' => 'annual_monitoring', 'reporting_year' => 2026]);
        $this->assertSame(0, $report->totals['activities']);
        $this->assertNotNull($report->filters['period_from']);
    }

    public function test_upload_preserves_versioned_annex_metadata_without_bypassing_quarantine(): void
    {
        $template = FormTemplate::where('policy_version_id', $this->policy->id)->where('code', 'saa')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('saa.pdf', "%PDF-1.4\nTest\n%%EOF");
        $doc = app(UploadDocument::class)->execute($this->hei, $this->institution->id,
            DocumentType::where('code', 'saa')->value('id'), $file, true, ['template_id' => $template->id, 'semester' => 'First', 'academic_year' => '2026-2027']);
        $this->assertSame('C', $doc->metadata['template']['annex']);
        $this->assertSame($this->policy->id, $doc->metadata['template']['policy_version_id']);
        $this->assertSame('pending', $doc->scan_status);
        $this->expectException(HttpException::class);
        app(UploadDocument::class)->execute($this->hei, $this->institution->id,
            DocumentType::where('code', 'itinerary')->value('id'), $file, true, ['template_id' => $template->id]);
    }

    public function test_institution_investigation_requires_clearance_for_another_activity(): void
    {
        $first = $this->activity();
        $second = $this->activity();
        $officer = $this->staff('ched_case_officer');
        $case = app(RecordOperationalRecord::class)->execute($officer, 'case', ['institution_id' => $this->institution->id,
            'activity_id' => $first->id, 'reference' => 'INSTITUTION-INV', 'type' => 'investigation', 'status' => 'investigating',
            'summary' => 'Authoritative off-campus investigation', 'opened_at' => '2026-10-07', 'source_document_id' => $this->document('official_order')->id]);
        $issues = app(ActivityReadiness::class)->issues($second);
        $this->assertContains('Activity-specific CEB clearance required for the current revision.', $issues);
        app(RecordCaseEvent::class)->execute($officer, $case, 'fact_finding', 'Evidence gathered', $this->document('supporting', false)->id);
        $this->assertSame('investigating', $case->fresh()->status);
        $this->assertDatabaseHas('case_events', ['case_record_id' => $case->id, 'type' => 'fact_finding']);
    }

    public function test_frozen_report_export_does_not_rebuild_from_later_submissions(): void
    {
        $this->submit($this->activity());
        $analyst = $this->staff('ched_analyst');
        $report = app(ManageRegionalReport::class)->prepare($analyst, ['period' => 'Frozen test', 'cutoff_at' => '2026-10-07 10:00', 'report_type' => 'regional']);
        $this->submit($this->activity());
        $export = Export::create(['requested_by' => $analyst->id, 'filters' => ['format' => 'csv', 'regional_report_id' => $report->id]]);
        app(GenerateExport::class, ['exportId' => $export->id])->handle();
        $this->assertSame('ready', $export->fresh()->status);
        $this->assertStringContainsString('activities,1', Storage::disk('private')->get($export->fresh()->path));
        $this->assertSame(2, app(RegionalReportBuilder::class)->build($analyst)['totals']['activities']);
    }

    public function test_frozen_report_export_rechecks_regional_authorization(): void
    {
        $analyst = $this->staff('ched_analyst');
        $report = app(ManageRegionalReport::class)->prepare($analyst, ['period' => 'Private regional test', 'cutoff_at' => '2026-10-07 10:00', 'report_type' => 'regional']);
        $other = $this->staff('ched_analyst');
        $other->forceFill(['region_id' => Region::create(['code' => 'OTHER', 'name' => 'Other region'])->id])->save();
        $export = Export::create(['requested_by' => $other->id, 'filters' => ['format' => 'csv', 'regional_report_id' => $report->id]]);
        $this->expectException(HttpException::class);
        app(GenerateExport::class, ['exportId' => $export->id])->handle();
    }

    public function test_annual_reminder_is_deduplicated_and_uses_ched_portal(): void
    {
        Notification::fake();
        $this->policy->update(['status' => 'confirmed', 'publication_on' => '2026-09-28', 'publication_reference' => 'TEST ONLY', 'effective_on' => '2026-10-13']);
        $analyst = $this->staff('ched_analyst');
        $this->travelTo(CarbonImmutable::parse('2027-08-15 08:00', 'Asia/Manila'));
        $this->artisan('lakbay:reminders')->assertSuccessful();
        $this->artisan('lakbay:reminders')->assertSuccessful();
        Notification::assertSentToTimes($analyst, WorkflowNotice::class, 1);
        Notification::assertSentTo($analyst, WorkflowNotice::class, fn ($notice) => $notice->panel === 'ched');
    }
}
