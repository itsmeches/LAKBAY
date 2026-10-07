<?php

namespace Tests\Feature;

use App\Actions\Activities\SaveActivity;
use App\Actions\Submissions\ReviewSubmission;
use App\Actions\Submissions\SubmitActivity;
use App\Filament\Hei\Resources\Pages\EditActivity;
use App\Filament\Hei\Resources\Pages\ViewActivity;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Services\Compliance\RequirementResolver;
use App\Services\Reporting\RacpBuilder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RacpTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Institution $institution;

    private AcademicPeriod $period;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
        Storage::fake('private');
        config(['lakbay.allow_draft_policy' => true]);
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'RACP-TEST',
            'name' => 'RACP Test HEI', 'address' => 'Calamba, Laguna', 'institution_type' => 'private']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Authorized representative', 'verified_at' => now(), 'is_authorized' => true]);
        $this->period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'AY 2026-2027',
            'term' => 'First semester', 'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, $this->activityData());
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function racpData(): array
    {
        $risks = [];
        foreach (config('racp.areas') as $code => $label) {
            $risks[$code] = ['level' => 'low', 'measure' => 'Use the documented safe procedure and coordinate with the PIC.'];
        }
        $contacts = [];
        foreach (config('racp.offices') as $code => $label) {
            $contacts[$code] = ['person' => 'Fictional '.$label.' Contact', 'details' => '09170000000'];
        }

        return ['risks' => $risks, 'contacts' => $contacts, 'other_risks' => [], 'other_contacts' => [],
            'signatories' => [
                'prepared_by' => ['name' => 'Fictional Preparer', 'position' => 'Coordinator', 'contact' => '09170000000', 'email' => 'preparer@example.test'],
                'reviewed_by' => ['name' => 'Fictional Reviewer', 'position' => 'Dean'],
                'noted_by' => ['name' => 'Fictional HEI Head', 'position' => 'President'],
            ]];
    }

    private function activityData(): array
    {
        return ['institution_id' => $this->institution->id, 'academic_period_id' => $this->period->id,
            'title' => 'Learning visit', 'category' => 2, 'academic_nature' => 'curricular', 'activity_type' => 'Field study', 'initiator' => 'hei',
            'description' => 'Observe professional practice', 'learning_objectives' => 'Learn on site', 'responsible_office' => 'Student Affairs',
            'starts_at' => '2026-12-31 01:00', 'ends_at' => '2027-01-01 17:00', 'venue' => 'Laguna and Quezon City',
            'student_count' => 20, 'other_count' => 3, 'conditions' => [], 'authorization' => ['official' => 'President', 'date' => '2026-10-01'],
            'personnel' => [['role' => 'pic', 'name' => 'PIC Person', 'position' => 'Faculty', 'contact' => 'pic@example.test'],
                ['role' => 'apic', 'name' => 'APIC Person', 'position' => 'Faculty', 'contact' => 'apic@example.test']],
            'racp' => $this->racpData()];
    }

    private function submit(?int $previousId = null): Submission
    {
        $version = $this->activity->latestVersion()->firstOrFail();
        $policy = PolicyVersion::where('code', 'LAKBAY-JULY-2026-DRAFT')->firstOrFail();
        $evidence = [];
        foreach (app(RequirementResolver::class)->resolve($version, $policy) as $rule) {
            $type = $rule->requirementDefinition->documentType;
            $document = Document::create(['institution_id' => $this->institution->id, 'document_type_id' => $type->id,
                'public_id' => (string) Str::uuid(), 'path' => 'fixtures/'.Str::uuid().'.pdf', 'original_name' => $type->code.'.pdf',
                'mime' => 'application/pdf', 'size' => 9, 'sha256' => hash('sha256', '%PDF-test'),
                'confidentiality' => 'institutional', 'scan_status' => 'clean', 'is_signed' => true, 'uploaded_by' => $this->hei->id]);
            $evidence[$version->id][$rule->id] = ['status' => 'submitted', 'document_ids' => [$document->id]];
        }

        return app(SubmitActivity::class)->execute($this->hei, [$this->activity->id], $policy->id, $evidence, previousId: $previousId);
    }

    public function test_annex_e_maps_all_risk_areas_contacts_and_activity_basic_information(): void
    {
        $data = app(RacpBuilder::class)->snapshot($this->activity->latestVersion);
        $this->assertCount(6, $data['areas']);
        $this->assertCount(5, $data['offices']);
        $this->assertSame('RACP Test HEI', $data['header']['hei_name']);
        $this->assertSame('PRIVATE', $data['header']['hei_type']);
        $this->assertSame(23, $data['basic']['participants']);
        $this->assertSame('PIC Person', $data['basic']['pic']);
        $this->assertSame('APIC Person', $data['basic']['apic']);
        $this->assertStringContainsString('31 Dec 2026, 01:00', $data['basic']['proposed_dates']);
        $this->assertSame('E', $data['template']['annex']);
        $this->assertSame(24, $data['template']['source_page']);
        $this->assertNull($data['issued_on']);
        $this->assertSame([], app(RacpBuilder::class)->missingFields($data));
        $preview = $this->get(route('racp.activity', $this->activity))->assertOk();
        foreach ($data['areas'] as $label) {
            $preview->assertSee($label);
        }
        foreach ($data['offices'] as $label) {
            $preview->assertSee($label);
        }
        $preview->assertSee('Other Risks (please specify)')->assertSee('IV. CERTIFICATION')
            ->assertSee('20____')->assertSee('completed prior to the conduct');
    }

    public function test_printable_racp_is_unsigned_and_does_not_change_evidence_or_authorization(): void
    {
        $authorization = $this->activity->latestVersion->authorization;
        $response = $this->get(route('racp.activity', ['activity' => $this->activity->id, 'download' => 1]));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        file_put_contents(storage_path('framework/testing/racp-layout.pdf'), $response->getContent());
        $this->assertSame(1, preg_match_all('~/Type\\s*/Page\\b~', $response->getContent()));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(0, Document::count());
        $this->assertSame(0, Submission::count());
        $this->assertSame($authorization, $this->activity->fresh()->latestVersion->authorization);
        $this->assertDatabaseHas('audit_events', ['action' => 'annex_e.generated']);
    }

    public function test_risk_levels_and_additional_risks_and_contacts_are_explicitly_entered(): void
    {
        $data = $this->activityData();
        $data['racp']['risks']['transport']['level'] = 'high';
        $data['racp']['risks']['weather']['level'] = 'medium';
        $data['racp']['other_risks'] = [['area' => 'Communication outage', 'level' => 'medium', 'measure' => 'Carry backup radio.']];
        $data['racp']['other_contacts'] = [['office' => 'Rescue unit', 'person' => 'Fictional Rescue Officer', 'details' => 'rescue@example.test']];
        $activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $snapshot = app(RacpBuilder::class)->snapshot($activity->latestVersion);
        $this->assertSame('high', $snapshot['risks']['transport']['level']);
        $this->assertSame('medium', $snapshot['risks']['weather']['level']);
        $this->assertSame('Communication outage', $snapshot['other_risks'][0]['area']);
        $this->get(route('racp.activity', $activity))->assertOk()->assertSee('High')->assertSee('Medium')
            ->assertSee('Communication outage')->assertSee('Carry backup radio.')->assertSee('Fictional Rescue Officer');
        $this->get(route('racp.activity', ['activity' => $activity->id, 'download' => 1]))->assertOk();
    }

    public function test_incomplete_racp_draft_is_saved_but_complete_printing_requires_measures_and_contacts(): void
    {
        $data = $this->activityData();
        $data['racp'] = ['risks' => ['transport' => ['level' => 'low']]];
        $activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->get(route('racp.activity', $activity))->assertOk()->assertSee('Complete these print fields')->assertSee('contingency measure');
        $this->get(route('racp.activity', ['activity' => $activity->id, 'download' => 1]))->assertStatus(422);
    }

    public function test_invalid_risk_level_is_rejected_without_creating_a_revision(): void
    {
        $data = $this->activityData();
        $data['racp']['risks']['transport']['level'] = 'critical';
        try {
            app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
            $this->fail('Unsupported risk level accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('racp.risks.transport.level', $exception->errors());
        }
        $this->assertSame(1, $this->activity->versions()->count());
    }

    public function test_blank_optional_form_does_not_invent_a_risk_assessment(): void
    {
        $data = $this->activityData();
        $data['racp'] = ['risks' => ['transport' => ['level' => null, 'measure' => '']],
            'issued_on' => null, 'signatories' => ['prepared_by' => ['name' => '', 'email' => null]]];
        $activity = app(SaveActivity::class)->execute($this->hei, $data);
        $this->assertNull($activity->latestVersion->racp);
        $this->get(route('racp.activity', $activity))->assertStatus(409);
    }

    public function test_livewire_revision_exposes_racp_inputs_and_preserves_previous_version(): void
    {
        $old = $this->activity->latestVersion;
        Livewire::test(EditActivity::class, ['record' => $this->activity->id])
            ->fillForm(['racp.risks.transport.level' => 'high', 'racp.risks.transport.measure' => 'Arrange inspected backup transport.',
                'racp.contacts.hei.person' => 'New Fictional Emergency Focal'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('low', $old->fresh()->racp['risks']['transport']['level']);
        $this->assertSame('high', $this->activity->fresh()->latestVersion->racp['risks']['transport']['level']);
        $this->assertSame('New Fictional Emergency Focal', $this->activity->fresh()->latestVersion->racp['contacts']['hei']['person']);
        Livewire::test(ViewActivity::class, ['record' => $this->activity->id])->assertActionExists('racp_preview');
    }

    public function test_omitting_racp_from_revision_preserves_existing_plan(): void
    {
        $old = $this->activity->latestVersion->racp;
        $data = $this->activityData();
        unset($data['racp']);
        $activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->assertSame($old, $activity->latestVersion->racp);
    }

    public function test_submissions_freeze_racp_header_areas_contacts_and_certification_across_corrections(): void
    {
        $submission = $this->submit();
        $oldId = $this->activity->latestVersion->id;
        $frozen = $submission->snapshot['annex_e'][$oldId];
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        app(ReviewSubmission::class)->execute($reviewer, $submission, 'corrections_requested', 'Revise the RACP.');
        $data = $this->activityData();
        $data['racp']['risks']['transport']['level'] = 'high';
        $data['racp']['contacts']['hei']['person'] = 'New Fictional Emergency Focal';
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->institution->update(['name' => 'Updated institution']);
        $revised = $this->submit($submission->id);
        $this->assertSame($frozen, $submission->fresh()->snapshot['annex_e'][$oldId]);
        $this->assertSame('high', $revised->snapshot['annex_e'][$this->activity->latestVersion->id]['risks']['transport']['level']);
        config(['racp.areas' => ['new' => 'Changed risk area'], 'racp.certification' => 'Changed certification']);
        $this->get(route('racp.submission', ['publicId' => $submission->public_id]))->assertOk()->assertSee('Learning visit');
        $this->get(route('racp.submission', ['publicId' => $submission->public_id, 'version' => $oldId]))->assertOk()
            ->assertSee('RACP Test HEI')->assertDontSee('Updated institution')->assertDontSee('Changed risk area')->assertDontSee('Changed certification');
        $this->get(route('racp.submission', ['publicId' => $submission->public_id, 'version' => $oldId, 'download' => 1]))->assertOk();
        $this->get(route('racp.submission', ['publicId' => $submission->public_id, 'version' => 999999]))->assertNotFound();
    }

    public function test_legacy_submission_and_unprepared_activity_are_not_reconstructed(): void
    {
        $data = $this->activityData();
        unset($data['racp']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data);
        $submission = $this->submit();
        $this->assertArrayNotHasKey('annex_e', $submission->snapshot);
        $this->get(route('racp.activity', $this->activity))->assertStatus(409);
        $this->get(route('racp.submission', ['publicId' => $submission->public_id]))->assertStatus(409);
    }

    public function test_scope_mfa_revoked_membership_and_ched_jurisdiction_are_enforced(): void
    {
        $submission = $this->submit();
        $foreign = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $foreign->assignRole('hei_submitter');
        $this->actingAs($foreign)->get(route('racp.activity', ['activity' => $this->activity->id, 'download' => 1]))->assertForbidden();
        $this->get(route('racp.submission', ['publicId' => $submission->public_id]))->assertForbidden();
        $reviewer = User::factory()->create(['region_id' => Region::create(['code' => 'III-E', 'name' => 'Other Region'])->id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer)->get(route('racp.activity', $this->activity))->assertForbidden();
        $reviewer->forceFill(['region_id' => $this->institution->region_id])->save();
        $this->actingAs($reviewer->fresh())->get(route('racp.activity', $this->activity))->assertOk();
        $this->hei->institutions()->updateExistingPivot($this->institution->id, ['verified_at' => null]);
        $this->actingAs($this->hei)->get(route('racp.activity', $this->activity))->assertForbidden();
        $this->hei->institutions()->updateExistingPivot($this->institution->id, ['verified_at' => now()]);
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->actingAs($this->hei->fresh())->get(route('racp.activity', $this->activity))->assertForbidden();
        auth()->logout();
        $this->get(route('racp.activity', $this->activity))->assertRedirect();
    }

    public function test_private_letterhead_quarantine_and_hash_checks_apply(): void
    {
        $image = UploadedFile::fake()->image('letterhead.png', 600, 80);
        $bytes = file_get_contents($image->getRealPath());
        Storage::disk('private')->put('racp-letterhead.png', $bytes);
        $document = Document::create(['institution_id' => $this->institution->id, 'document_type_id' => DocumentType::where('code', 'racp')->firstOrFail()->id,
            'public_id' => (string) Str::uuid(), 'path' => 'racp-letterhead.png', 'original_name' => 'letterhead.png', 'mime' => 'image/png',
            'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'confidentiality' => 'institutional', 'scan_status' => 'clean', 'is_signed' => false, 'uploaded_by' => $this->hei->id]);
        $data = $this->activityData();
        $data['racp']['letterhead_document_id'] = $document->id;
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $url = route('racp.activity', $this->activity);
        $this->get($url)->assertOk()->assertSee('data:image/png;base64,', false);
        $document->update(['scan_status' => 'pending']);
        $this->get($url)->assertStatus(409);
        $document->update(['scan_status' => 'clean']);
        Storage::disk('private')->put($document->path, 'changed bytes');
        $this->get($url)->assertStatus(409);
    }

    public function test_optional_issuance_date_prints_without_recording_signing(): void
    {
        $data = $this->activityData();
        $data['racp']['issued_on'] = '2026-10-15';
        $activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->get(route('racp.activity', $activity))->assertOk()->assertSee('Issued this 15 day of October, 2026.');
        $this->get(route('racp.activity', ['activity' => $activity->id, 'download' => 1]))->assertOk();
        $this->assertSame(0, Document::count());
        $this->assertSame(0, Submission::count());
    }

    public function test_added_rows_require_complete_print_fields_and_are_bounded(): void
    {
        $data = $this->activityData();
        $data['racp']['other_risks'] = [['area' => 'Communication outage']];
        $data['racp']['other_contacts'] = [['office' => 'Rescue unit']];
        $activity = app(SaveActivity::class)->execute($this->hei, $data);
        $this->get(route('racp.activity', ['activity' => $activity->id, 'download' => 1]))->assertStatus(422);
        $data['racp']['other_risks'] = array_fill(0, 11, ['area' => 'Test risk', 'level' => 'low', 'measure' => 'Test measure']);
        try {
            app(SaveActivity::class)->execute($this->hei, $data);
            $this->fail('Unbounded additional rows accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('racp.other_risks', $exception->errors());
        }
    }

    public function test_long_measures_paginate_and_dynamic_fields_are_escaped(): void
    {
        $data = $this->activityData();
        foreach ($data['racp']['risks'] as &$risk) {
            $risk['measure'] = str_repeat('Detailed fictional contingency measure. ', 25);
        }
        unset($risk);
        $data['racp']['signatories']['prepared_by']['name'] = '<script>alert(1)</script>';
        $activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->get(route('racp.activity', $activity))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
        $response = $this->get(route('racp.activity', ['activity' => $activity->id, 'download' => 1]))->assertOk();
        $this->assertGreaterThan(1, preg_match_all('~/Type\\s*/Page\\b~', $response->getContent()));
        file_put_contents(storage_path('framework/testing/racp-long-layout.pdf'), $response->getContent());
    }
}
