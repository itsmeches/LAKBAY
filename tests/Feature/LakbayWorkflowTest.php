<?php

namespace Tests\Feature;

use App\Actions\Activities\RecordActivityConclusion;
use App\Actions\Activities\SaveActivity;
use App\Actions\Clearances\RecordClearanceDecision;
use App\Actions\Clearances\RecordReconsideration;
use App\Actions\Clearances\RecordReconsiderationDecision;
use App\Actions\Documents\RegisterPublicTemplate;
use App\Actions\Documents\RequestEvidence;
use App\Actions\Documents\UploadDocument;
use App\Actions\Identity\InviteUser;
use App\Actions\Operations\ManageCorrectiveAction;
use App\Actions\Operations\RecordCaseEvent;
use App\Actions\Operations\RecordOperationalRecord;
use App\Actions\Reports\SubmitReport;
use App\Actions\Submissions\IssueAcknowledgment;
use App\Actions\Submissions\ReviewSubmission;
use App\Actions\Submissions\SubmitActivity;
use App\Actions\Submissions\SubmitCalendar;
use App\Filament\Ched\Pages\PolicyConfiguration;
use App\Filament\Ched\Resources\Pages\ListSubmission;
use App\Filament\Ched\Resources\Pages\ViewSubmission;
use App\Filament\Hei\Resources\Pages\EditActivity;
use App\Filament\Hei\Resources\Pages\ListAcademicPeriod;
use App\Filament\Hei\Resources\Pages\ListActivity;
use App\Filament\Hei\Resources\Pages\ListCalendar;
use App\Filament\Hei\Resources\Pages\ListDocument;
use App\Filament\Hei\Resources\Pages\ViewActivity;
use App\Filament\Support\Fields;
use App\Jobs\GenerateExport;
use App\Jobs\NotifyInstitution;
use App\Jobs\ScanDocument;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\Campus;
use App\Models\CaseRecord;
use App\Models\ClearanceRequest;
use App\Models\CorrectiveAction;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Export;
use App\Models\FormTemplate;
use App\Models\Holiday;
use App\Models\Institution;
use App\Models\InstitutionalRestriction;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\InvitationNotice;
use App\Notifications\WorkflowNotice;
use App\Services\Access;
use App\Services\Compliance\ActivityReadiness;
use App\Services\Compliance\DeadlineCalculator;
use App\Services\Compliance\RequirementResolver;
use App\Services\Reporting\RegionalReportBuilder;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LakbayWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $hei;

    protected Institution $institution;

    protected Calendar $calendar;

    protected Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
        Storage::fake('private');
        config(['lakbay.allow_draft_policy' => true]);
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'TEST-1', 'name' => 'Test HEI', 'institution_type' => 'private']);
        $this->hei = User::factory()->create();
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Representative', 'is_authorized' => true, 'verified_at' => now()]);
        $this->hei->forceFill(['app_authentication_secret' => 'TESTSECRET'])->save();
        $p = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'AY 2026', 'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->calendar = Calendar::create(['institution_id' => $this->institution->id, 'academic_period_id' => $p->id, 'title' => 'Annual CLOCA', 'responsible_office' => 'Student Affairs', 'created_by' => $this->hei->id]);
        $this->activity = app(SaveActivity::class)->execute($this->hei, $this->data());
    }

    public function test_activity_timestamps_render_in_manila_without_changing_versions(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        $this->actingAs($this->hei);
        $before = $this->activity->latestVersion->getRawOriginal();
        Livewire::test(ListActivity::class)
            ->assertTableColumnFormattedStateSet('latestVersion.starts_at', '10 Dec 2026, 09:00', $this->activity)
            ->assertSee('Starts At (Asia/Manila)')
            ->assertSee('Category');
        Livewire::test(ViewActivity::class, ['record' => $this->activity->getRouteKey()])
            ->assertSee('10 Dec 2026, 09:00');
        $this->assertSame($before, $this->activity->latestVersion->fresh()->getRawOriginal());
    }

    public function test_submission_timestamp_and_boolean_display_preserve_snapshot(): void
    {
        $submission = $this->submit()->fresh();
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        $this->actingAs($this->hei);
        $before = $submission->getRawOriginal();
        $expected = $submission->submitted_at->setTimezone('Asia/Manila')->format('d M Y, H:i');
        Livewire::test(\App\Filament\Hei\Resources\Pages\ListSubmission::class)
            ->assertTableColumnFormattedStateSet('submitted_at', $expected, $submission)
            ->assertTableColumnFormattedStateSet('is_late', $submission->is_late ? 'Yes' : 'No', $submission);
        Livewire::test(\App\Filament\Hei\Resources\Pages\ViewSubmission::class, ['record' => $submission->getRouteKey()])
            ->assertSee($expected)
            ->assertSee('Submitted At (Asia/Manila)');
        $this->assertSame($before, $submission->fresh()->getRawOriginal());
    }

    public function test_date_only_fields_keep_their_calendar_date(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        $this->actingAs($this->hei);
        $period = $this->calendar->academicPeriod;
        Livewire::test(ListAcademicPeriod::class)
            ->assertTableColumnFormattedStateSet('starts_on', '01 Nov 2026', $period);
        $this->assertSame('2026-11-01', $period->fresh()->starts_on->format('Y-m-d'));
    }

    private function data(): array
    {
        return ['institution_id' => $this->institution->id, 'academic_period_id' => $this->calendar->academic_period_id, 'calendar_id' => $this->calendar->id, 'title' => 'Field study', 'category' => 1, 'academic_nature' => 'curricular', 'activity_type' => 'Field study', 'initiator' => 'hei', 'description' => 'Field learning', 'learning_objectives' => 'Observe practice', 'responsible_office' => 'Student Affairs', 'starts_at' => '2026-12-10 09:00', 'ends_at' => '2026-12-10 17:00', 'venue' => 'CALABARZON', 'student_count' => 20, 'conditions' => [], 'authorization' => ['official' => 'HEI Official', 'date' => '2026-09-01'], 'personnel' => [['role' => 'pic', 'name' => 'Test PIC', 'position' => 'Faculty', 'contact' => 'contact@example.test', 'first_aid_trained' => true]]];
    }

    private function staff(string $role): User
    {
        $u = User::factory()->create();
        $u->forceFill(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET'])->save();
        $u->assignRole($role);

        return $u;
    }

    private function document(string $code): Document
    {
        $t = DocumentType::where('code', $code)->firstOrFail();
        $path = 'test/'.Str::uuid().'.pdf';
        Storage::disk('private')->put($path, '%PDF-test');

        return Document::create(['institution_id' => $this->institution->id, 'document_type_id' => $t->id, 'public_id' => (string) Str::uuid(), 'path' => $path, 'original_name' => $code.'.pdf', 'mime' => 'application/pdf', 'size' => 9, 'sha256' => hash('sha256', '%PDF-test'), 'confidentiality' => $t->confidentiality, 'scan_status' => 'clean', 'is_signed' => true, 'uploaded_by' => $this->hei->id]);
    }

    private function imageDocument(string $code = 'certification'): Document
    {
        $image = UploadedFile::fake()->image('evidence.png');
        $bytes = file_get_contents($image->getRealPath());
        $path = 'test/'.Str::uuid().'.png';
        Storage::disk('private')->put($path, $bytes);

        return Document::create([
            'institution_id' => $this->institution->id,
            'document_type_id' => DocumentType::where('code', $code)->firstOrFail()->id,
            'public_id' => (string) Str::uuid(),
            'disk' => 'private', 'path' => $path, 'original_name' => 'evidence.png',
            'mime' => 'image/png', 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes),
            'confidentiality' => $code === 'ceb_decision' ? 'restricted' : 'institutional',
            'scan_status' => 'clean', 'is_signed' => false, 'uploaded_by' => $this->hei->id,
        ]);
    }

    private function submit(?int $previous = null): Submission
    {
        $v = $this->activity->latestVersion()->first();
        $p = PolicyVersion::first();
        $e = [];
        foreach (app(RequirementResolver::class)->resolve($v, $p) as $r) {
            $e[$v->id][$r->id] = ['status' => 'submitted', 'document_ids' => [$this->document($r->requirementDefinition->code)->id]];
        }

        return app(SubmitCalendar::class)->execute($this->hei, $this->calendar, $p->id, $e, $previous);
    }

    public function test_correction_and_acknowledgment_preserve_history(): void
    {
        $s = $this->submit();
        $snapshot = $s->snapshot;
        app(ReviewSubmission::class)->execute($this->staff('ched_reviewer'), $s, 'corrections_requested', 'Update certification.');
        $this->activity = app(SaveActivity::class)->execute($this->hei, array_replace($this->data(), ['title' => 'Revised']), $this->activity);
        $new = $this->submit($s->id);
        $this->assertSame($snapshot, $s->fresh()->snapshot);
        $this->assertSame('superseded', $s->fresh()->status);
        $this->assertSame(2, $new->revision);
        app(ReviewSubmission::class)->execute($this->staff('ched_reviewer'), $new, 'complete', 'Complete for records.');
        $a = app(IssueAcknowledgment::class)->execute($this->staff('ched_signatory'), $new, ['document_id' => $this->document('acknowledgment')->id, 'reference' => 'ACK-TEST', 'recipient' => 'HEI', 'signatory' => 'Director', 'letter_date' => '2026-10-06']);
        $this->assertSame('acknowledged', $new->fresh()->status);
        $this->assertSame('planned', $this->activity->fresh()->execution_status);
        $this->assertStringContainsString('not CHED approval', $a->summary['disclaimer']);
    }

    public function test_submission_snapshot_is_immutable(): void
    {
        $s = $this->submit();
        $this->expectException(\LogicException::class);
        $s->update(['snapshot' => []]);
    }

    public function test_activity_versions_are_immutable(): void
    {
        $this->expectException(\LogicException::class);
        $this->activity->latestVersion->update(['title' => 'Changed']);
    }

    public function test_other_hei_cannot_access_records(): void
    {
        $i = Institution::create(['region_id' => $this->institution->region_id, 'ched_code' => 'OTHER', 'name' => 'Other', 'institution_type' => 'private']);
        $u = User::factory()->create();
        $u->assignRole('hei_submitter');
        $u->forceFill(['app_authentication_secret' => 'SECRET'])->save();
        $u->institutions()->attach($i->id, ['position' => 'Officer', 'is_authorized' => true, 'verified_at' => now()]);
        $this->assertFalse(app(Access::class)->owns($u, $this->institution->id));
        $this->assertSame(0, app(Access::class)->scope(Activity::query(), $u)->count());
        $this->actingAs($u)->get('/hei/activities/'.$this->activity->id)->assertNotFound();
    }

    public function test_clean_image_preview_is_private_inline_and_audited(): void
    {
        $document = $this->imageDocument();
        $image = UploadedFile::fake()->image('evidence.png');
        Storage::disk('private')->put($document->path, file_get_contents($image->getRealPath()));
        $this->actingAs($this->hei)->get(route('documents.preview', $document->public_id))
            ->assertOk()->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'inline; filename=evidence.png');
        $this->assertDatabaseHas('audit_events', ['action' => 'document.previewed', 'record_id' => $document->id]);
        $this->get('/hei/documents/'.$document->id)->assertOk()->assertSee(route('documents.preview', $document->public_id), false);
    }

    public function test_image_preview_requires_authentication_and_institutional_access(): void
    {
        $document = $this->imageDocument();
        $url = route('documents.preview', $document->public_id);
        $this->get($url)->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['app_authentication_secret' => 'SECRET']))->get($url)->assertForbidden();
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->actingAs($this->hei)->get($url)->assertForbidden();
        $this->assertDatabaseMissing('audit_events', ['action' => 'document.previewed', 'record_id' => $document->id]);
    }

    public function test_preview_blocks_pending_infected_and_non_image_documents(): void
    {
        $document = $this->document('certification');
        $this->actingAs($this->hei);
        $url = route('documents.preview', $document->public_id);
        $this->get($url)->assertStatus(415);
        $document = $this->imageDocument();
        $url = route('documents.preview', $document->public_id);
        $document->update(['scan_status' => 'pending']);
        $this->get($url)->assertStatus(409);
        $this->get('/hei/documents/'.$document->id)->assertOk()->assertSee('awaiting malware scanning')->assertDontSee($url, false);
        $document->update(['scan_status' => 'infected']);
        $this->get($url)->assertStatus(409);
        $document->update(['scan_status' => 'clean']);
        Storage::disk('private')->delete($document->path);
        $this->get($url)->assertNotFound();
        $this->assertDatabaseMissing('audit_events', ['action' => 'document.previewed', 'record_id' => $document->id]);
    }

    public function test_administrator_cannot_preview_restricted_images(): void
    {
        $document = $this->imageDocument('ceb_decision');
        $this->actingAs($this->staff('system_administrator'))
            ->get(route('documents.preview', $document->public_id))->assertForbidden();
    }

    public function test_scanner_uses_configured_portable_database_directory(): void
    {
        $document = $this->document('certification');
        $document->update(['scan_status' => 'pending']);
        config(['lakbay.scanner_command' => 'clamscan', 'lakbay.scanner_database_path' => 'C:/scanner/database']);
        Process::fake(['*' => Process::result(exitCode: 0)]);
        (new ScanDocument($document->id))->handle();
        Process::assertRan(fn ($process) => $process->command === ['clamscan', '--no-summary', '--database='.str_replace('/', DIRECTORY_SEPARATOR, 'C:/scanner/database'), realpath(Storage::disk('private')->path($document->path))]);
        $this->assertSame('clean', $document->fresh()->scan_status);
    }

    public function test_private_download_and_quarantine(): void
    {
        $d = $this->document('cloca');
        $this->actingAs($this->hei)->get(route('documents.download', $d->public_id))->assertOk();
        $d->update(['scan_status' => 'pending']);
        $this->get(route('documents.download', $d->public_id))->assertStatus(409);
        $other = User::factory()->create();
        $other->forceFill(['app_authentication_secret' => 'SECRET'])->save();
        $this->actingAs($other)->get(route('documents.download', $d->public_id))->assertForbidden();
    }

    public function test_admin_cannot_read_restricted_documents(): void
    {
        $d = $this->document('ceb_decision');
        $this->actingAs($this->staff('system_administrator'))->get(route('documents.download', $d->public_id))->assertForbidden();
    }

    public function test_draft_policy_guard(): void
    {
        config(['lakbay.allow_draft_policy' => false]);
        $this->expectException(ValidationException::class);
        $this->submit();
    }

    public function test_category_three_retains_declaration_when_evidence_escalates(): void
    {
        $d = $this->data();
        $d['category'] = 3;
        $d['initiator'] = 'student';
        $d['conditions'] = ['requires_accommodation' => true];
        $a = app(SaveActivity::class)->execute($this->hei, $d);
        $this->assertSame(3, $a->latestVersion->category);
        $this->assertSame(2, $a->latestVersion->requirement_category);
    }

    public function test_working_day_deadline_uses_versioned_holidays(): void
    {
        Holiday::create(['date' => '2026-10-12', 'name' => 'Test holiday', 'scope' => 'national', 'calendar_version' => 'test']);
        $d = app(DeadlineCalculator::class)->calculate('working_days_before', 3, '2026-10-14 09:00', 'test');
        $this->assertSame('2026-10-08 09:00', $d->timezone('Asia/Manila')->format('Y-m-d H:i'));
    }

    public function test_late_incident_is_accepted_without_signed_document(): void
    {
        $r = app(SubmitReport::class)->execute($this->hei, 'incident', ['activity_id' => $this->activity->id, 'occurred_at' => now()->subDays(3)->format('Y-m-d H:i:s'), 'location' => 'Field', 'type' => 'Injury', 'description' => 'Known facts', 'immediate_actions' => 'First aid', 'current_status' => 'Recovering']);
        $this->assertTrue($r->is_late);
        $this->assertTrue($r->provisional_deadline);
        $this->assertNull($r->document_id);
    }

    public function test_unscanned_evidence_is_rejected(): void
    {
        $v = $this->activity->latestVersion;
        $p = PolicyVersion::first();
        $e = [];
        foreach (app(RequirementResolver::class)->resolve($v, $p) as $r) {
            $d = $this->document($r->requirementDefinition->code);
            $d->update(['scan_status' => 'pending']);
            $e[$v->id][$r->id] = ['status' => 'submitted', 'document_ids' => [$d->id]];
        }
        $this->expectException(ValidationException::class);
        app(SubmitCalendar::class)->execute($this->hei, $this->calendar, $p->id, $e);
    }

    public function test_duplicate_acknowledgment_is_rejected(): void
    {
        $s = $this->submit();
        app(ReviewSubmission::class)->execute($this->staff('ched_reviewer'), $s, 'complete', 'Complete');
        $u = $this->staff('ched_signatory');
        $d = ['document_id' => $this->document('acknowledgment')->id, 'reference' => 'ACK-ONE', 'recipient' => 'HEI', 'signatory' => 'Director', 'letter_date' => '2026-10-06'];
        app(IssueAcknowledgment::class)->execute($u, $s, $d);
        $this->expectException(ValidationException::class);
        app(IssueAcknowledgment::class)->execute($u, $s, $d);
    }

    public function test_reconsideration_is_limited_to_one_per_denial(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 10:00', 'Asia/Manila'));
        $u = $this->staff('ched_case_officer');
        $c = CaseRecord::create(['institution_id' => $this->institution->id, 'jurisdiction_region_id' => $this->institution->region_id, 'reference' => 'CASE-1', 'type' => 'investigation', 'summary' => 'Restricted', 'status' => 'investigating', 'opened_at' => now(), 'created_by' => $u->id]);
        $r = ClearanceRequest::create(['institution_id' => $this->institution->id, 'activity_version_id' => $this->activity->latestVersion->id, 'case_record_id' => $c->id, 'justification' => 'Grounds', 'created_by' => $u->id]);
        app(RecordClearanceDecision::class)->execute($u, $r, 'denied', $this->document('ceb_decision')->id, '2026-10-01 09:00', '2026-10-02 09:00');
        $m = app(RecordReconsideration::class)->execute($u, $r, '2026-10-10 09:00', 'Grounds', $this->document('reconsideration')->id);
        $this->assertFalse($m->is_late);
        $this->expectException(ValidationException::class);
        app(RecordReconsideration::class)->execute($u, $r, '2026-10-11 09:00', 'Again', $this->document('reconsideration')->id);
    }

    public function test_regional_report_deduplicates_revisions(): void
    {
        $s = $this->submit();
        app(ReviewSubmission::class)->execute($this->staff('ched_reviewer'), $s, 'corrections_requested', 'Correct');
        $this->submit($s->id);
        $r = app(RegionalReportBuilder::class)->build($this->staff('ched_analyst'));
        $this->assertSame(1, $r['totals']['activities']);
        $this->assertSame(20, $r['totals']['estimated_student_participations']);
    }

    public function test_workspace_prioritizes_corrections_and_links_to_the_precise_submission(): void
    {
        $submission = $this->submit();
        app(ReviewSubmission::class)->execute($this->staff('ched_reviewer'), $submission, 'corrections_requested', 'Replace the signed form.');
        $other = $this->submit($submission->id);
        $otherInstitution = Institution::create(['region_id' => $this->institution->region_id, 'ched_code' => 'OTHER-UI', 'name' => 'Other HEI', 'institution_type' => 'private']);
        $foreign = $other->replicate();
        $foreign->public_id = (string) Str::uuid();
        $foreign->institution_id = $otherInstitution->id;
        $foreign->reference = 'FOREIGN-PRIVATE-REFERENCE';
        $foreign->save();
        app(ReviewSubmission::class)->execute($this->staff('ched_reviewer'), $other, 'corrections_requested', 'Update the evidence.');

        $this->actingAs($this->hei)->get('/hei')
            ->assertRedirect('/hei/overview');
        $this->get('/hei/overview')->assertOk()
            ->assertSee('1 submission(s) need corrections')
            ->assertSee('Your activity workflow')
            ->assertSee('/hei/submissions/'.$other->id, false)
            ->assertDontSee('FOREIGN-PRIVATE-REFERENCE')
            ->assertDontSee('Administration and reporting');
    }

    public function test_ched_workspace_exposes_only_authorized_administration_shortcuts(): void
    {
        $this->submit();
        $this->actingAs($this->staff('ched_reviewer'))->get('/ched/overview')
            ->assertOk()->assertSee('1 awaiting review')->assertSee('Your review workflow')
            ->assertDontSee('Invite an institutional or CHED user')
            ->assertDontSee('Manage policy requirements and templates');
        $this->actingAs($this->staff('system_administrator'))->get('/ched/overview')
            ->assertOk()->assertSee('Invite an institutional or CHED user')
            ->assertSee('Manage policy requirements and templates');
    }

    public function test_record_page_allows_the_same_submission_workflow_as_the_list(): void
    {
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        Livewire::test(ViewActivity::class, ['record' => $this->activity->id])
            ->assertActionExists('submit')
            ->assertActionExists('revise');
        $submission = $this->submit();
        $this->actingAs($this->staff('ched_reviewer'));
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        Livewire::test(ViewSubmission::class, ['record' => $submission->id])
            ->callAction('review', data: ['outcome' => 'corrections_requested', 'remarks' => 'Correct the attachment.'])
            ->assertHasNoActionErrors();
        $this->assertSame('corrections_requested', $submission->fresh()->status);
    }

    public function test_panel_routes_render(): void
    {
        $this->get('/hei/login')->assertOk();
        $this->get('/ched/login')->assertOk();
        $this->get('/guidelines')->assertOk();
        $this->get('/faq')->assertOk();
        $this->actingAs($this->hei)->get('/hei/activities')->assertOk();
        $this->get('/hei/calendars')->assertOk();
        $this->get('/hei/documents')->assertOk();
        $this->get('/hei/overview')->assertOk();
        $this->actingAs($this->staff('ched_case_officer'))->get('/ched/case-records')->assertOk();
        $this->actingAs($this->staff('ched_analyst'))->get('/ched/reporting')->assertOk();
    }

    public function test_filament_activity_creation_and_submission_modal(): void
    {
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        Livewire::test(ListActivity::class)->callAction('create', data: array_replace($this->data(), ['title' => 'From panel', 'other_count' => 0]))->assertHasNoActionErrors();
        $this->assertDatabaseHas('activities', ['title' => 'From panel', 'institution_id' => $this->institution->id]);
        Livewire::test(ListCalendar::class)->mountAction(TestAction::make('submit')->table($this->calendar))->assertActionMounted();
    }

    public function test_document_requests_preserve_frozen_submission(): void
    {
        $s = $this->submit();
        $original = $s->snapshot;
        $t = DocumentType::where('code', 'racp')->first();
        $r = app(RequestEvidence::class)->execute($this->staff('ched_reviewer'), $s, ['document_type_id' => $t->id, 'reason' => 'Provide retained RACP', 'due_at' => '2026-10-20 09:00']);
        app(RequestEvidence::class)->respond($this->hei, $r, $this->document('racp')->id);
        $this->assertSame('submitted', $r->fresh()->status);
        $this->assertSame($original, $s->fresh()->snapshot);
    }

    public function test_confirmed_policy_and_referenced_holidays_are_immutable(): void
    {
        $s = $this->submit();
        $p = PolicyVersion::first();
        $p->update(['status' => 'confirmed', 'confirmed_by' => $this->hei->id, 'confirmed_at' => now()]);
        try {
            $p->update(['title' => 'Edited']);
            $this->fail('Confirmed policy was changed');
        } catch (\LogicException) {
            $this->assertTrue(true);
        }
        $this->expectException(\LogicException::class);
        Holiday::create(['date' => '2026-10-12', 'name' => 'Late addition', 'calendar_version' => $s->holiday_calendar_version]);
    }

    public function test_quarantine_scan_is_fail_closed_and_hash_checked(): void
    {
        $d = $this->document('cloca');
        $d->update(['scan_status' => 'pending']);
        config(['lakbay.scanner_command' => 'fake-scanner']);
        Process::fake(['*' => Process::result(exitCode: 0)]);
        app(ScanDocument::class, ['documentId' => $d->id])->handle();
        $this->assertSame('clean', $d->fresh()->scan_status);
    }

    public function test_csv_export_sanitizes_formulas_and_pdf_renders(): void
    {
        $this->activity = app(SaveActivity::class)->execute($this->hei, array_replace($this->data(), ['title' => '=FORMULA']), $this->activity);
        $this->submit();
        $u = $this->staff('ched_analyst');
        foreach (['csv', 'pdf'] as $format) {
            $e = Export::create(['requested_by' => $u->id, 'filters' => ['format' => $format]]);
            (new GenerateExport($e->id))->handle();
            $contents = Storage::disk('private')->get($e->fresh()->path);
            if ($format === 'csv') {
                $this->assertStringContainsString("'=FORMULA", $contents);
            } else {
                $this->assertStringStartsWith('%PDF', $contents);
            }
        }
    }

    public function test_invitation_is_expiring_and_single_use(): void
    {
        $u = $this->staff('system_administrator');
        Notification::fake();
        $i = app(InviteUser::class)->execute($u, ['name' => 'New representative', 'email' => 'new@example.test', 'position' => 'Officer', 'role' => 'hei_submitter', 'institution_id' => $this->institution->id]);
        $this->assertTrue($i->expires_at->isFuture());
        $this->assertSame(64, strlen($i->token_hash));
        Notification::assertSentOnDemand(InvitationNotice::class, function ($notice) {
            $token = $notice->token;
            $this->post('/invitations/'.$token, ['password' => 'A-secure-password-123', 'password_confirmation' => 'A-secure-password-123'])->assertRedirect('/');
            $this->post('/invitations/'.$token, ['password' => 'A-secure-password-123', 'password_confirmation' => 'A-secure-password-123'])->assertStatus(410);

            return true;
        });
        $this->assertNotNull($i->fresh()->accepted_at);
    }

    public function test_policy_admin_actions_mount(): void
    {
        $this->actingAs($this->staff('system_administrator'));
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        Livewire::test(PolicyConfiguration::class)->mountAction('confirm_policy')->assertActionMounted();
        $this->get('/ched/document-requests')->assertOk();
    }

    public function test_corrective_follow_up_requires_hei_response_before_ched_closure(): void
    {
        $staff = $this->staff('ched_monitor');
        $items = collect(config('monitoring.criteria'))->map(fn ($text, $code) => ['criterion_code' => (string) $code, 'compliance' => 'yes', 'verification' => 'Signed records'])->values()->all();
        $monitoring = app(RecordOperationalRecord::class)->execute($staff, 'monitoring', [
            'institution_id' => $this->institution->id, 'period' => '2026', 'monitored_on' => '2026-10-06',
            'type' => 'desk', 'respondent' => 'Officer', 'overall_rating' => 'compliant', 'findings' => 'Follow up', 'items' => $items,
        ]);
        $this->assertSame(14, $monitoring->items()->count());
        $action = app(ManageCorrectiveAction::class);
        $record = $action->create($staff, $monitoring, ['action' => 'Update evidence', 'responsible_party' => 'HEI', 'due_on' => '2026-10-20']);
        $action->respond($this->hei, $record, 'Evidence updated');
        $action->close($staff, $record);
        $this->assertSame('closed', $record->fresh()->status);
        $this->actingAs($this->hei)->get('/hei/corrective-actions')->assertOk();
        $this->actingAs($staff)->get('/ched/corrective-actions')->assertOk();
    }

    public function test_final_reconsideration_decision_preserves_denial_and_clears_exact_version(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 10:00', 'Asia/Manila'));
        $u = $this->staff('ched_case_officer');
        $c = CaseRecord::create(['institution_id' => $this->institution->id, 'jurisdiction_region_id' => $this->institution->region_id, 'reference' => 'CASE-FINAL', 'type' => 'investigation', 'summary' => 'Restricted', 'status' => 'investigating', 'opened_at' => now(), 'created_by' => $u->id]);
        $r = ClearanceRequest::create(['institution_id' => $this->institution->id, 'activity_version_id' => $this->activity->latestVersion->id, 'case_record_id' => $c->id, 'justification' => 'Grounds', 'created_by' => $u->id]);
        app(RecordClearanceDecision::class)->execute($u, $r, 'denied', $this->document('ceb_decision')->id, '2026-10-01 09:00', '2026-10-02 09:00');
        app(RecordReconsideration::class)->execute($u, $r, '2026-10-10 09:00', 'Grounds', $this->document('reconsideration')->id);
        app(RecordReconsiderationDecision::class)->execute($u, $r, 'granted', $this->document('ceb_decision')->id);
        $this->assertSame('denied', $r->fresh()->decision);
        $this->assertSame([], app(ActivityReadiness::class)->issues($this->activity));
        $this->activity = app(SaveActivity::class)->execute($this->hei, array_replace($this->data(), ['title' => 'Changed scope']), $this->activity);
        $this->assertNotEmpty(app(ActivityReadiness::class)->issues($this->activity));
    }

    public function test_case_closure_rejects_unsigned_order(): void
    {
        $u = $this->staff('ched_case_officer');
        $c = CaseRecord::create(['institution_id' => $this->institution->id, 'jurisdiction_region_id' => $this->institution->region_id, 'reference' => 'CASE-UNSIGNED', 'type' => 'investigation', 'summary' => 'Restricted', 'status' => 'investigating', 'opened_at' => now(), 'created_by' => $u->id]);
        $d = $this->document('cloca');
        $this->expectException(HttpException::class);
        app(RecordCaseEvent::class)->execute($u, $c, 'closed', 'Closure recorded', $d->id);
    }

    public function test_only_confirmed_approved_blank_templates_are_public(): void
    {
        $this->freezeTime();
        $t = FormTemplate::where('code', 'cloca')->firstOrFail();
        $d = $this->document('cloca');
        $d = $d->replicate()->fill(['public_id' => (string) Str::uuid(), 'is_signed' => false]);
        $d->save();
        app(RegisterPublicTemplate::class)->execute($this->staff('system_administrator'), $t, $d->id);
        $this->get(route('templates.download', $t->id))->assertNotFound();

        $t->policyVersion->update(['status' => 'confirmed', 'effective_on' => now()->subDay()->toDateString(), 'confirmed_by' => $this->hei->id, 'confirmed_at' => now()]);
        $this->get(route('templates.download', $t->id))->assertOk();
        $this->get('/guidelines')->assertSee(route('templates.download', $t->id));
        $this->get(route('documents.download', $d->public_id))->assertRedirect('/login');
    }

    public function test_signed_submission_cannot_be_registered_as_public_template(): void
    {
        $t = FormTemplate::where('code', 'cloca')->firstOrFail();
        $d = $this->document('cloca');

        try {
            app(RegisterPublicTemplate::class)->execute($this->staff('system_administrator'), $t, $d->id);
            $this->fail('Signed submission was accepted as a public template.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        $this->assertFalse($t->fresh()->is_public);
        $this->get(route('templates.download', $t->id))->assertNotFound();
    }

    public function test_calendar_submission_from_filament_records_frozen_package(): void
    {
        $policy = PolicyVersion::first();
        $evidence = [];
        foreach (app(RequirementResolver::class)->resolve($this->activity->latestVersion, $policy) as $rule) {
            $evidence[$rule->id] = ['status' => 'submitted', 'document_ids' => [$this->document($rule->requirementDefinition->code)->id]];
        }
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));

        Livewire::test(ListCalendar::class)
            ->callAction(TestAction::make('submit')->table($this->calendar), data: ['policy_id' => $policy->id, 'evidence' => $evidence])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('submissions', ['institution_id' => $this->institution->id, 'calendar_id' => $this->calendar->id, 'status' => 'submitted']);
        Queue::assertPushed(NotifyInstitution::class);
    }

    public function test_retained_evidence_cannot_attach_other_institution_document_metadata(): void
    {
        $other = Institution::create(['region_id' => $this->institution->region_id, 'ched_code' => 'OTHER-EVIDENCE', 'name' => 'Other HEI', 'institution_type' => 'private']);
        $foreign = $this->document('racp')->replicate()->fill(['institution_id' => $other->id, 'public_id' => (string) Str::uuid()]);
        $foreign->save();
        $data = array_replace($this->data(), ['category' => 2, 'calendar_id' => null]);
        $activity = app(SaveActivity::class)->execute($this->hei, $data);
        $version = $activity->latestVersion;
        $policy = PolicyVersion::first();
        $evidence = [];
        foreach (app(RequirementResolver::class)->resolve($version, $policy) as $rule) {
            $evidence[$version->id][$rule->id] = $rule->evidence_mode === 'certified_retained'
                ? ['status' => 'certified_retained', 'reason' => 'Signed certification on file', 'document_ids' => [$foreign->id]]
                : ['status' => 'submitted', 'document_ids' => [$this->document($rule->requirementDefinition->code)->id]];
        }

        $submission = app(SubmitActivity::class)->execute($this->hei, [$activity->id], $policy->id, $evidence);

        $this->assertNotContains($foreign->id, array_column($submission->snapshot['documents'], 'id'));
        $this->assertSame([], $submission->requirements()->where('evidence_status', 'certified_retained')->first()->document_ids);
    }

    public function test_category_three_rejects_hei_initiation(): void
    {
        $data = array_replace($this->data(), ['category' => 3]);
        $this->expectException(ValidationException::class);

        app(SaveActivity::class)->execute($this->hei, $data);
    }

    public function test_reclassification_requires_a_reason(): void
    {
        $data = array_replace($this->data(), ['category' => 2]);
        $this->expectException(ValidationException::class);

        app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
    }

    public function test_calendar_readiness_includes_restrictions_on_later_activities(): void
    {
        $later = app(SaveActivity::class)->execute($this->hei, array_replace($this->data(), ['title' => 'Restricted later activity']));
        InstitutionalRestriction::create(['institution_id' => $this->institution->id, 'activity_id' => $later->id, 'type' => 'suspension', 'effective_from' => now()->subDay()->toDateString(), 'issuing_authority' => 'CHED', 'document_id' => $this->document('official_order')->id, 'created_by' => $this->hei->id]);

        $issues = app(ActivityReadiness::class)->summary($this->calendar);

        $this->assertContains('Restricted later activity: An active signed suspension applies.', $issues);
    }

    public function test_actual_conclusion_records_the_completion_report_anchor(): void
    {
        $this->freezeTime();

        app(RecordActivityConclusion::class)->execute($this->hei, $this->activity, now()->subHour()->timezone('Asia/Manila')->format('Y-m-d H:i:s'));

        $this->assertSame('completed', $this->activity->fresh()->execution_status);
        $this->assertNotNull($this->activity->fresh()->actual_ended_at);
        $this->assertDatabaseHas('audit_events', ['action' => 'activity.concluded', 'record_id' => $this->activity->id]);
    }

    public static function accountRoles(): array
    {
        return [
            'HEI preparer' => ['hei_preparer', true, false, false],
            'HEI submitter' => ['hei_submitter', true, false, false],
            'CHED records' => ['ched_records', false, true, false],
            'CHED reviewer' => ['ched_reviewer', false, true, false],
            'CHED signatory' => ['ched_signatory', false, true, false],
            'CHED monitor' => ['ched_monitor', false, true, false],
            'CHED case officer' => ['ched_case_officer', false, true, true],
            'CHED analyst' => ['ched_analyst', false, true, false],
            'System administrator' => ['system_administrator', false, true, false],
        ];
    }

    #[DataProvider('accountRoles')]
    public function test_account_roles_enforce_panel_and_restricted_document_permissions(string $role, bool $heiPanel, bool $chedPanel, bool $restricted): void
    {
        $actor = $this->staff($role);
        if ($heiPanel) {
            $actor->institutions()->attach($this->institution->id, ['position' => 'Officer', 'is_authorized' => $role === 'hei_submitter', 'verified_at' => now()]);
        }
        $document = $this->document('ceb_decision');

        $this->assertSame($heiPanel, $actor->canAccessPanel(Filament::getPanel('hei')));
        $this->assertSame($chedPanel, $actor->canAccessPanel(Filament::getPanel('ched')));
        $this->assertSame($restricted, Gate::forUser($actor)->allows('view', $document));
        $this->assertSame($restricted, app(Access::class)->document($actor, $document));
    }

    public static function blockedAccounts(): array
    {
        return ['inactive' => ['inactive'], 'unverified email' => ['email'], 'MFA not enrolled' => ['mfa']];
    }

    #[DataProvider('blockedAccounts')]
    public function test_private_download_rejects_ineligible_accounts(string $reason): void
    {
        $document = $this->document('cloca');
        $this->hei->forceFill(match ($reason) {
            'inactive' => ['is_active' => false],
            'email' => ['email_verified_at' => null],
            'mfa' => ['app_authentication_secret' => null],
        })->save();

        $this->actingAs($this->hei)->get(route('documents.download', $document->public_id))->assertForbidden();
        $this->assertDatabaseMissing('audit_events', ['action' => 'document.downloaded', 'record_id' => $document->id]);
    }

    public function test_unverified_or_unauthorized_membership_cannot_submit(): void
    {
        $this->hei->institutions()->updateExistingPivot($this->institution->id, ['is_authorized' => false]);

        try {
            $this->submit();
            $this->fail('Unauthorized membership submitted a package.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('submissions', 0);
        Queue::assertNothingPushed();
    }

    public function test_other_region_reviewer_cannot_review_or_select_records(): void
    {
        $submission = $this->submit();
        $region = Region::create(['code' => 'III', 'name' => 'Other Region']);
        $actor = $this->staff('ched_reviewer');
        $actor->forceFill(['region_id' => $region->id])->save();
        $this->actingAs($actor);

        $this->assertSame([], Fields::institutions());
        $this->assertSame([], Fields::related(Activity::class));
        $this->get('/ched/submissions/'.$submission->id)->assertNotFound();
        try {
            app(ReviewSubmission::class)->execute($actor, $submission, 'complete', 'Invalid jurisdiction');
            $this->fail('Other-region review accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame('submitted', $submission->fresh()->status);
        $this->assertDatabaseCount('submission_reviews', 0);
    }

    public static function scannerFailures(): array
    {
        return [
            'scanner missing' => ['', 0, false, 'pending', true],
            'infected file' => ['fake-scanner', 1, false, 'infected', false],
            'scanner unavailable' => ['fake-scanner', 2, false, 'pending', true],
            'file tampered after upload' => ['fake-scanner', 0, true, 'pending', true],
        ];
    }

    #[DataProvider('scannerFailures')]
    public function test_scanner_failure_paths_never_release_quarantine(string $command, int $exitCode, bool $tamper, string $status, bool $throws): void
    {
        $document = $this->document('cloca');
        $document->update(['scan_status' => 'pending']);
        config(['lakbay.scanner_command' => $command]);
        Process::fake(['*' => Process::result(exitCode: $exitCode)]);
        if ($tamper) {
            Storage::disk('private')->put($document->path, 'Changed contents');
        }
        $failure = null;

        try {
            (new ScanDocument($document->id))->handle();
        } catch (\RuntimeException $exception) {
            $failure = $exception;
        }

        $this->assertSame($throws, $failure !== null);
        $this->assertSame($status, $document->fresh()->scan_status);
        $this->actingAs($this->hei)->get(route('documents.download', $document->public_id))->assertStatus(409);
    }

    public function test_temporary_png_upload_accepts_the_document_limit_and_returns_validation_errors_above_it(): void
    {
        $this->actingAs($this->hei);
        Storage::fake('tmp-for-tests');
        $url = app(GenerateSignedUploadUrl::class)->forLocal();
        $this->postJson($url, ['files' => [UploadedFile::fake()->image('evidence.png')->size(13000)]])
            ->assertOk()->assertJsonStructure(['paths']);
        $this->postJson($url, ['files' => [UploadedFile::fake()->image('too-large.png')->size(20481)]])
            ->assertUnprocessable()->assertJsonValidationErrors(['files.0']);
    }

    public function test_hei_document_form_accepts_png_supporting_evidence(): void
    {
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        Livewire::test(ListDocument::class)
            ->callAction('create', data: [
                'institution_id' => $this->institution->id,
                'document_type_id' => DocumentType::where('code', 'certification')->firstOrFail()->id,
                'is_signed' => false,
                'file' => UploadedFile::fake()->image('supporting-evidence.png'),
            ])->assertHasNoActionErrors();
        $this->assertDatabaseHas('documents', ['original_name' => 'supporting-evidence.png', 'scan_status' => 'pending', 'is_signed' => false]);
        Queue::assertPushed(ScanDocument::class);
    }

    public function test_signed_png_is_rejected_without_storing_an_official_document(): void
    {
        $this->expectException(ValidationException::class);
        app(UploadDocument::class)->execute(
            $this->hei,
            $this->institution->id,
            DocumentType::where('code', 'cloca')->firstOrFail()->id,
            UploadedFile::fake()->image('signed.png'),
            true,
        );
    }

    public function test_upload_creates_private_quarantined_document_and_scan_job(): void
    {
        $file = UploadedFile::fake()->createWithContent('evidence.pdf', "%PDF-1.4\nTest evidence\n%%EOF");
        $type = DocumentType::where('code', 'cloca')->firstOrFail();

        $document = app(UploadDocument::class)->execute($this->hei, $this->institution->id, $type->id, $file, true);

        $this->assertSame('pending', $document->scan_status);
        $this->assertSame('private', $document->disk);
        Storage::disk('private')->assertExists($document->path);
        $this->assertSame(hash('sha256', Storage::disk('private')->get($document->path)), $document->sha256);
        Queue::assertPushed(ScanDocument::class, fn ($job) => $job->documentId === $document->id);
    }

    public function test_upload_rejects_executable_and_does_not_queue_scan(): void
    {
        $fake = UploadedFile::fake()->createWithContent('fake.pdf', '<?php echo "executable";');
        $file = new UploadedFile($fake->getRealPath(), 'fake.pdf', null, null, true);
        $type = DocumentType::where('code', 'cloca')->firstOrFail();

        try {
            app(UploadDocument::class)->execute($this->hei, $this->institution->id, $type->id, $file, true);
            $this->fail('Executable masquerading as PDF accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }
        $this->assertDatabaseCount('documents', 0);
        Queue::assertNothingPushed();
    }

    public function test_hei_cannot_upload_restricted_case_document(): void
    {
        $file = UploadedFile::fake()->createWithContent('order.pdf', "%PDF-1.4\nTest\n%%EOF");
        $type = DocumentType::where('code', 'official_order')->firstOrFail();

        try {
            app(UploadDocument::class)->execute($this->hei, $this->institution->id, $type->id, $file, true);
            $this->fail('HEI uploaded restricted case evidence.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('documents', 0);
        Queue::assertNothingPushed();
    }

    public function test_expired_invitation_cannot_create_account(): void
    {
        $this->freezeTime();
        Notification::fake();
        $invitation = app(InviteUser::class)->execute($this->staff('system_administrator'), ['name' => 'Expired Invite', 'email' => 'expired@example.test', 'position' => 'Officer', 'role' => 'hei_submitter', 'institution_id' => $this->institution->id]);
        $token = null;
        Notification::assertSentOnDemand(InvitationNotice::class, function ($notice) use (&$token) {
            $token = $notice->token;

            return true;
        });
        $this->travel(4)->days();

        $this->get('/invitations/'.$token)->assertNotFound();
        $this->post('/invitations/'.$token, ['password' => 'Long-test-password-123', 'password_confirmation' => 'Long-test-password-123'])->assertGone();
        $this->assertNull($invitation->fresh()->accepted_at);
        $this->assertDatabaseMissing('users', ['email' => 'expired@example.test']);
    }

    public function test_invitation_payload_cannot_escalate_role_or_institution(): void
    {
        Notification::fake();
        app(InviteUser::class)->execute($this->staff('system_administrator'), ['name' => 'Representative', 'email' => 'locked-role@example.test', 'position' => 'Officer', 'role' => 'hei_preparer', 'institution_id' => $this->institution->id]);
        $token = null;
        Notification::assertSentOnDemand(InvitationNotice::class, function ($notice) use (&$token) {
            $token = $notice->token;

            return true;
        });

        $this->post('/invitations/'.$token, ['password' => 'Long-test-password-123', 'password_confirmation' => 'Long-test-password-123', 'role' => 'system_administrator', 'region_id' => $this->institution->region_id, 'institution_id' => 99999])->assertRedirect('/');

        $user = User::where('email', 'locked-role@example.test')->firstOrFail();
        $this->assertSame(['hei_preparer'], $user->getRoleNames()->all());
        $this->assertNull($user->region_id);
        $this->assertFalse(app(Access::class)->owns($user, $this->institution->id, true));
    }

    public function test_mfa_secrets_are_encrypted_and_excluded_from_serialization(): void
    {
        $stored = DB::table('users')->where('id', $this->hei->id)->value('app_authentication_secret');

        $this->assertNotSame('TESTSECRET', $stored);
        $this->assertSame('TESTSECRET', $this->hei->fresh()->getAppAuthenticationSecret());
        $this->assertArrayNotHasKey('app_authentication_secret', $this->hei->toArray());
        $this->assertArrayNotHasKey('app_authentication_recovery_codes', $this->hei->toArray());
    }

    public function test_full_filament_correction_resubmission_and_acknowledgment_cycle(): void
    {
        $reviewer = $this->staff('ched_reviewer');
        $signatory = $this->staff('ched_signatory');
        $policy = PolicyVersion::first();
        $evidence = [];
        foreach (app(RequirementResolver::class)->resolve($this->activity->latestVersion, $policy) as $rule) {
            $evidence[$rule->id] = ['status' => 'submitted', 'document_ids' => [$this->document($rule->requirementDefinition->code)->id]];
        }
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        Livewire::test(ListCalendar::class)->callAction(TestAction::make('submit')->table($this->calendar), data: ['policy_id' => $policy->id, 'evidence' => $evidence])->assertHasNoActionErrors();
        $original = Submission::firstOrFail();
        $snapshot = $original->snapshot;

        $this->actingAs($reviewer);
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        Livewire::test(ListSubmission::class)->callAction(TestAction::make('review')->table($original), data: ['outcome' => 'corrections_requested', 'remarks' => 'Clarify activity title'])->assertHasNoActionErrors();
        $this->assertSame('corrections_requested', $original->fresh()->status);

        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        Livewire::test(EditActivity::class, ['record' => $this->activity->id])->fillForm(['title' => 'Corrected field study', 'other_count' => 0])->call('save')->assertHasNoFormErrors();
        $this->assertSame(2, $this->activity->fresh()->versions()->count());
        Livewire::test(ListCalendar::class)->callAction(TestAction::make('submit')->table($this->calendar), data: ['policy_id' => $policy->id, 'previous_id' => $original->id, 'evidence' => $evidence])->assertHasNoActionErrors();
        $revision = Submission::latest('id')->firstOrFail();

        $this->actingAs($reviewer);
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        Livewire::test(ListSubmission::class)->callAction(TestAction::make('review')->table($revision), data: ['outcome' => 'complete', 'remarks' => 'Complete for records'])->assertHasNoActionErrors();
        $document = $this->document('acknowledgment');
        $this->actingAs($signatory);
        Livewire::test(ListSubmission::class)->callAction(TestAction::make('acknowledge')->table($revision), data: ['document_id' => $document->id, 'reference' => 'ACK-UI-TEST', 'recipient' => 'Test HEI', 'signatory' => 'Test Director', 'letter_date' => '2026-10-06'])->assertHasNoActionErrors();

        $this->assertSame('acknowledged', $revision->fresh()->status);
        $this->assertSame('superseded', $original->fresh()->status);
        $this->assertSame($snapshot, $original->fresh()->snapshot);
        $this->assertSame($revision->id, $revision->fresh()->acknowledgment->submission_id);
        $this->actingAs($this->hei)->get(route('documents.download', $document->public_id))->assertOk();
        $this->assertDatabaseHas('audit_events', ['action' => 'acknowledgment.issued', 'record_id' => $revision->id]);
    }

    public static function activityCategories(): array
    {
        return ['Category 2' => [2, 'hei', 2, []], 'Category 3 basic' => [3, 'student', 3, []], 'Category 3 strenuous' => [3, 'student', 2, ['strenuous' => true]], 'Category 3 distance' => [3, 'student', 2, ['long_distance' => true]]];
    }

    #[DataProvider('activityCategories')]
    public function test_non_calendar_categories_submit_with_applicable_evidence(int $category, string $initiator, int $requirementsCategory, array $conditions): void
    {
        $data = array_replace($this->data(), ['category' => $category, 'initiator' => $initiator, 'conditions' => $conditions, 'calendar_id' => null]);
        $activity = app(SaveActivity::class)->execute($this->hei, $data);
        $version = $activity->latestVersion;
        $policy = PolicyVersion::first();
        $evidence = [];
        foreach (app(RequirementResolver::class)->resolve($version, $policy) as $rule) {
            $evidence[$version->id][$rule->id] = $rule->evidence_mode === 'certified_retained'
                ? ['status' => 'certified_retained', 'reason' => 'Certification reference TEST-RETAINED']
                : ['status' => 'submitted', 'document_ids' => [$this->document($rule->requirementDefinition->code)->id]];
        }

        $submission = app(SubmitActivity::class)->execute($this->hei, [$activity->id], $policy->id, $evidence);

        $this->assertSame('activity', $submission->type);
        $this->assertSame($category, $submission->snapshot['activities'][0]['category']);
        $this->assertSame($requirementsCategory, $submission->snapshot['activities'][0]['requirement_category']);
        $this->assertGreaterThan(0, $submission->requirements()->count());
        Queue::assertPushed(NotifyInstitution::class);
    }

    public function test_duplicate_submission_is_rejected_without_side_effects(): void
    {
        $this->submit();
        Queue::fake();

        try {
            $this->submit();
            $this->fail('Duplicate submission accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('submission', $exception->errors());
        }
        $this->assertDatabaseCount('submissions', 1);
        Queue::assertNothingPushed();
    }

    public function test_acknowledgment_before_review_is_rejected(): void
    {
        $submission = $this->submit();

        try {
            app(IssueAcknowledgment::class)->execute($this->staff('ched_signatory'), $submission, ['document_id' => $this->document('acknowledgment')->id, 'reference' => 'ACK-PREMATURE', 'recipient' => 'Test HEI', 'signatory' => 'Director', 'letter_date' => '2026-10-06']);
            $this->fail('Unreviewed package acknowledged.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('submission', $exception->errors());
        }
        $this->assertSame('submitted', $submission->fresh()->status);
        $this->assertDatabaseCount('acknowledgments', 0);
    }

    public function test_second_review_cannot_overwrite_first_review(): void
    {
        $submission = $this->submit();
        app(ReviewSubmission::class)->execute($this->staff('ched_reviewer'), $submission, 'complete', 'Complete');

        try {
            app(ReviewSubmission::class)->execute($this->staff('ched_reviewer'), $submission, 'corrections_requested', 'Overwrite');
            $this->fail('Second review overwrote first.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('submission', $exception->errors());
        }
        $this->assertDatabaseCount('submission_reviews', 1);
        $this->assertSame('ready_for_acknowledgment', $submission->fresh()->status);
    }

    public function test_draft_mode_is_refused_in_production_even_when_flag_enabled(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['lakbay.allow_draft_policy' => true]);

        try {
            $this->submit();
            $this->fail('Production accepted draft policy.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('policy', $exception->errors());
        }
        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_future_policy_is_refused_before_effective_date(): void
    {
        $this->freezeTime();
        PolicyVersion::first()->update(['effective_on' => now()->addMonth()->toDateString()]);

        try {
            $this->submit();
            $this->fail('Future policy accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('policy', $exception->errors());
        }
        $this->assertDatabaseCount('submissions', 0);
    }

    public static function deadlineExamples(): array
    {
        return [
            'calendar days' => ['calendar_days_before', 30, '2026-11-01 00:00', '2026-10-02 00:00'],
            'elapsed hours' => ['hours_after', 24, '2026-10-06 15:30', '2026-10-07 15:30'],
            'receipt based' => ['calendar_days_after', 15, '2026-10-02 09:00', '2026-10-17 09:00'],
            'weekend crossing' => ['working_days_before', 3, '2026-10-12 09:00', '2026-10-07 09:00'],
        ];
    }

    #[DataProvider('deadlineExamples')]
    public function test_deadline_types_preserve_manila_time(string $type, int $amount, string $anchor, string $expected): void
    {
        $date = app(DeadlineCalculator::class)->calculate($type, $amount, $anchor, 'empty-test-calendar');

        $this->assertSame($expected, $date->timezone('Asia/Manila')->format('Y-m-d H:i'));
        $this->assertSame('UTC', $date->timezoneName);
    }

    public function test_local_holidays_apply_only_to_the_matching_locality_and_version(): void
    {
        Holiday::create(['date' => '2026-10-12', 'name' => 'Local holiday', 'scope' => 'local', 'locality' => 'Calamba', 'calendar_version' => 'local-test']);
        $calculator = app(DeadlineCalculator::class);

        $this->assertSame('2026-10-09 09:00', $calculator->calculate('working_days_before', 1, '2026-10-13 09:00', 'local-test', 'Calamba')->timezone('Asia/Manila')->format('Y-m-d H:i'));
        $this->assertSame('2026-10-12 09:00', $calculator->calculate('working_days_before', 1, '2026-10-13 09:00', 'local-test', 'Other City')->timezone('Asia/Manila')->format('Y-m-d H:i'));
        $this->assertSame('2026-10-12 09:00', $calculator->calculate('working_days_before', 1, '2026-10-13 09:00', 'different-version', 'Calamba')->timezone('Asia/Manila')->format('Y-m-d H:i'));
    }

    public function test_incident_reported_now_in_manila_is_accepted(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-06 02:00:00', 'UTC'));

        $report = app(SubmitReport::class)->execute($this->hei, 'incident', ['activity_id' => $this->activity->id, 'occurred_at' => '2026-10-06 10:00:00', 'location' => 'Field', 'type' => 'Injury', 'description' => 'Facts', 'immediate_actions' => 'First aid', 'current_status' => 'Stable']);

        $this->assertSame('2026-10-06 02:00:00', $report->occurred_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-07 10:00', $report->due_at->timezone('Asia/Manila')->format('Y-m-d H:i'));
        $this->assertFalse($report->is_late);
    }

    public function test_future_manila_incident_is_rejected_without_record(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-06 02:00:00', 'UTC'));

        try {
            app(SubmitReport::class)->execute($this->hei, 'incident', ['activity_id' => $this->activity->id, 'occurred_at' => '2026-10-06 10:01:00', 'location' => 'Field', 'type' => 'Injury', 'description' => 'Facts', 'immediate_actions' => 'First aid', 'current_status' => 'Stable']);
            $this->fail('Future incident accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('occurred_at', $exception->errors());
        }
        $this->assertDatabaseCount('incident_reports', 0);
    }

    public function test_completion_report_freezes_deadline_reference_and_marks_activity_completed(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-06 02:00:00', 'UTC'));
        $submission = $this->submit();

        $report = app(SubmitReport::class)->execute($this->hei, 'completion', ['activity_id' => $this->activity->id, 'ended_at' => '2026-10-05 16:00:00', 'venue' => 'Test venue', 'student_count' => 20, 'implementation' => 'as_authorized', 'summary' => 'Completed', 'incidents_encountered' => false, 'document_id' => $this->document('completion')->id]);

        $this->assertSame('completed', $this->activity->fresh()->execution_status);
        $this->assertSame('2026-10-08 16:00', $report->due_at->timezone('Asia/Manila')->format('Y-m-d H:i'));
        $this->assertSame($submission->policy_version_id, $report->policy_version_id);
        $this->assertSame(72, $report->deadline_parameters['amount']);
        $this->assertFalse($report->is_late);
    }

    public function test_change_report_preserves_original_and_records_differences(): void
    {
        $original = $this->activity->latestVersion;
        $revised = app(SaveActivity::class)->execute($this->hei, array_replace($this->data(), ['venue' => 'Revised venue']), $this->activity)->latestVersion;

        $report = app(SubmitReport::class)->execute($this->hei, 'change', ['activity_id' => $this->activity->id, 'original_version_id' => $original->id, 'revised_version_id' => $revised->id, 'changed_at' => now()->subDay()->timezone('Asia/Manila')->format('Y-m-d H:i:s'), 'reason' => 'Host changed', 'document_id' => $this->document('change')->id]);

        $this->assertSame('CALABARZON', $original->fresh()->venue);
        $this->assertDatabaseHas('change_report_items', ['change_report_id' => $report->id, 'field' => 'venue', 'previous_value' => 'CALABARZON', 'revised_value' => 'Revised venue']);
    }

    public function test_incident_supplement_links_to_the_same_activity(): void
    {
        $data = ['activity_id' => $this->activity->id, 'occurred_at' => now()->subDay()->timezone('Asia/Manila')->format('Y-m-d H:i:s'), 'location' => 'Field', 'type' => 'Injury', 'description' => 'Facts', 'immediate_actions' => 'First aid', 'current_status' => 'Stable'];
        $original = app(SubmitReport::class)->execute($this->hei, 'incident', $data);

        $supplement = app(SubmitReport::class)->execute($this->hei, 'incident', $data + ['supplements_report_id' => $original->id]);

        $this->assertSame($original->id, $supplement->supplements_report_id);
        $this->assertDatabaseCount('incident_reports', 2);
    }

    public function test_export_download_is_private_and_expires(): void
    {
        $this->freezeTime();
        $this->submit();
        $actor = $this->staff('ched_analyst');
        $export = Export::create(['requested_by' => $actor->id, 'filters' => ['format' => 'csv']]);
        (new GenerateExport($export->id))->handle();

        $this->actingAs($actor)->get(route('exports.download', $export))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->staff('ched_analyst'))->get(route('exports.download', $export))->assertForbidden();
        $this->actingAs($actor);
        $this->travel(2)->days();
        $this->get(route('exports.download', $export))->assertGone();
    }

    public function test_export_job_rechecks_revoked_permission(): void
    {
        $actor = $this->staff('ched_analyst');
        $export = Export::create(['requested_by' => $actor->id, 'filters' => ['format' => 'csv']]);
        $actor->removeRole('ched_analyst');

        try {
            (new GenerateExport($export->id))->handle();
            $this->fail('Export generated after permission revocation.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertNull($export->fresh()->path);
    }

    public function test_notification_retry_does_not_repeat_completed_delivery(): void
    {
        Notification::fake();
        $job = new NotifyInstitution($this->institution->id, 'test-deduplication', 'Notice', 'Sign in');

        $job->handle();
        $job->handle();

        Notification::assertSentToTimes($this->hei, WorkflowNotice::class, 1);
        $this->assertDatabaseCount('notification_deliveries', 1);
    }

    public function test_wrong_evidence_type_response_is_rejected(): void
    {
        $submission = $this->submit();
        $request = app(RequestEvidence::class)->execute($this->staff('ched_reviewer'), $submission, ['document_type_id' => DocumentType::where('code', 'racp')->value('id'), 'reason' => 'Provide RACP', 'due_at' => '2026-10-20 09:00']);

        try {
            app(RequestEvidence::class)->respond($this->hei, $request, $this->document('cloca')->id);
            $this->fail('Wrong evidence type accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        $this->assertSame('requested', $request->fresh()->status);
        $this->assertNull($request->fresh()->response_document_id);
    }

    public function test_monitoring_with_missing_criteria_rolls_back(): void
    {
        $actor = $this->staff('ched_monitor');

        try {
            app(RecordOperationalRecord::class)->execute($actor, 'monitoring', ['institution_id' => $this->institution->id, 'period' => '2026', 'monitored_on' => '2026-10-06', 'type' => 'desk', 'respondent' => 'Officer', 'overall_rating' => 'compliant', 'findings' => 'Review', 'items' => []]);
            $this->fail('Incomplete monitoring matrix accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }
        $this->assertDatabaseCount('monitoring_records', 0);
        $this->assertDatabaseCount('monitoring_items', 0);
    }

    public function test_corrective_action_cannot_close_without_hei_response(): void
    {
        $actor = $this->staff('ched_monitor');
        $record = CorrectiveAction::create(['institution_id' => $this->institution->id, 'action' => 'Provide evidence', 'responsible_party' => 'HEI', 'due_on' => '2026-10-20', 'created_by' => $actor->id]);

        try {
            app(ManageCorrectiveAction::class)->close($actor, $record);
            $this->fail('Corrective action closed without response.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $this->assertSame('open', $record->fresh()->status);
    }

    public function test_submission_deadline_uses_activity_campus_local_holidays(): void
    {
        $campus = Campus::create(['institution_id' => $this->institution->id, 'name' => 'Calamba Campus', 'locality' => 'Calamba']);
        Holiday::create(['date' => '2026-10-12', 'name' => 'Calamba holiday', 'scope' => 'local', 'locality' => 'Calamba', 'calendar_version' => 'campus-test']);
        config(['lakbay.holiday_calendar_version' => 'campus-test']);
        $data = array_replace($this->data(), ['category' => 2, 'calendar_id' => null, 'campus_id' => $campus->id, 'starts_at' => '2026-10-14 09:00', 'ends_at' => '2026-10-14 17:00']);
        $activity = app(SaveActivity::class)->execute($this->hei, $data);
        $version = $activity->latestVersion;
        $policy = PolicyVersion::first();
        $evidence = [];
        foreach (app(RequirementResolver::class)->resolve($version, $policy) as $rule) {
            $evidence[$version->id][$rule->id] = $rule->evidence_mode === 'certified_retained'
                ? ['status' => 'certified_retained', 'reason' => 'Certification TEST']
                : ['status' => 'submitted', 'document_ids' => [$this->document($rule->requirementDefinition->code)->id]];
        }

        $submission = app(SubmitActivity::class)->execute($this->hei, [$activity->id], $policy->id, $evidence);

        $this->assertSame('2026-10-08 09:00', $submission->due_at->timezone('Asia/Manila')->format('Y-m-d H:i'));
        $this->assertSame('campus-test', $submission->holiday_calendar_version);
    }

    public static function invalidActivityInputs(): array
    {
        return [
            'empty title' => [['title' => ''], 'title'],
            'unsupported category' => [['category' => 4], 'category'],
            'negative student count' => [['student_count' => -1], 'student_count'],
            'no participants' => [['student_count' => 0], 'student_count'],
            'end before start' => [['ends_at' => '2026-12-10 08:00'], 'ends_at'],
            'invalid start date' => [['starts_at' => 'not-a-date'], 'starts_at'],
            'injected condition predicate' => [['conditions' => ['execute_command' => true]], 'conditions'],
            'nonboolean condition' => [['conditions' => ['strenuous' => 'sometimes']], 'conditions.strenuous'],
            'missing personnel' => [['personnel' => []], 'personnel'],
            'no designated PIC' => [['personnel' => [['role' => 'supervisor', 'name' => 'Staff', 'position' => 'Faculty', 'contact' => 'staff@example.test']]], 'personnel'],
        ];
    }

    #[DataProvider('invalidActivityInputs')]
    public function test_invalid_activity_input_does_not_create_partial_records(array $changes, string $field): void
    {
        $data = array_replace($this->data(), $changes);

        try {
            app(SaveActivity::class)->execute($this->hei, $data);
            $this->fail('Invalid activity accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseCount('activity_versions', 1);
    }

    public function test_correct_category_change_records_reason_and_preserves_old_version(): void
    {
        $original = $this->activity->latestVersion;

        $activity = app(SaveActivity::class)->execute($this->hei, array_replace($this->data(), ['category' => 2, 'classification_reason' => 'Changed scope requires Category 2 evidence']), $this->activity);

        $this->assertSame(1, $original->fresh()->category);
        $this->assertSame(2, $activity->latestVersion->category);
        $this->assertDatabaseHas('activity_classifications', ['activity_version_id' => $activity->latestVersion->id, 'declared_category' => 2, 'reason' => 'Changed scope requires Category 2 evidence']);
    }

    public function test_valid_password_requires_mfa_before_login(): void
    {
        $this->hei->forceFill(['password' => 'Test-password-123', 'app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        $login = Livewire::test(Login::class)->fillForm(['email' => $this->hei->email, 'password' => 'Test-password-123'])->call('authenticate')->assertHasNoFormErrors();

        $this->assertGuest();
        $this->assertNotNull($login->get('userUndertakingMultiFactorAuthentication'));
        $code = AppAuthentication::make()->getCurrentCode($this->hei);
        $login->set('data.multiFactor.app.code', $code)->call('authenticate')->assertHasNoErrors();

        $this->assertAuthenticatedAs($this->hei);
    }

    public function test_wrong_mfa_code_does_not_authenticate(): void
    {
        $this->hei->forceFill(['password' => 'Test-password-123', 'app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        $login = Livewire::test(Login::class)->fillForm(['email' => $this->hei->email, 'password' => 'Test-password-123'])->call('authenticate');

        $login->set('data.multiFactor.app.code', 'abcdef')->call('authenticate')->assertHasErrors();
        $this->assertGuest();
    }

    public function test_ched_account_cannot_login_to_hei_panel(): void
    {
        $actor = $this->staff('ched_reviewer');
        $actor->forceFill(['password' => 'Test-password-123'])->save();
        Filament::setCurrentPanel(Filament::getPanel('hei'));

        Livewire::test(Login::class)->fillForm(['email' => $actor->email, 'password' => 'Test-password-123'])->call('authenticate')->assertHasFormErrors(['email']);
        $this->assertGuest();
    }
}
