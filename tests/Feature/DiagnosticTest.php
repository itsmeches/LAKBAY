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
use App\Services\Reporting\DiagnosticBuilder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DiagnosticTest extends TestCase
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
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'DIAGNOSTIC-TEST',
            'name' => 'Diagnostic Test HEI', 'address' => 'Calamba, Laguna', 'institution_type' => 'private']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Authorized representative', 'verified_at' => now(), 'is_authorized' => true]);
        $this->period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'AY 2026-2027',
            'term' => 'First semester', 'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, $this->activityData());
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function diagnosticData(): array
    {
        $answers = [];
        foreach (config('diagnostic.groups') as $criteria) {
            foreach ($criteria as $code => $question) {
                $answers[$code] = ['answer' => 'yes', 'remarks' => ''];
            }
        }

        return ['answers' => $answers, 'recommendation' => 'proceed', 'signatories' => [
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
            'diagnostic' => $this->diagnosticData()];
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

    public function test_annex_d_basic_information_and_all_twenty_criteria_are_mapped(): void
    {
        $data = app(DiagnosticBuilder::class)->snapshot($this->activity->latestVersion);
        $this->assertSame(20, count($data['answers']));
        $this->assertCount(5, $data['groups']);
        $this->assertSame('Diagnostic Test HEI', $data['header']['hei_name']);
        $this->assertSame('PRIVATE', $data['header']['hei_type']);
        $this->assertSame(23, $data['basic']['participants']);
        $this->assertSame('PIC Person', $data['basic']['pic']);
        $this->assertSame('APIC Person', $data['basic']['apic']);
        $this->assertStringContainsString('31 Dec 2026, 01:00', $data['basic']['proposed_dates']);
        $this->assertSame('D', $data['template']['annex']);
        $this->assertSame(23, $data['template']['source_page']);
        $this->assertSame([], app(DiagnosticBuilder::class)->missingFields($data));
        $preview = $this->get(route('diagnostic.activity', $this->activity))->assertOk();
        foreach ($data['groups'] as $criteria) {
            foreach ($criteria as $question) {
                $preview->assertSee($question);
            }
        }
    }

    public function test_printable_diagnostic_pdf_is_unsigned_and_does_not_change_evidence_or_authorization(): void
    {
        $authorization = $this->activity->latestVersion->authorization;
        $response = $this->get(route('diagnostic.activity', ['activity' => $this->activity->id, 'download' => 1]));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        file_put_contents(storage_path('framework/testing/diagnostic-layout.pdf'), $response->getContent());
        $this->assertSame(1, preg_match_all('~/Type\s*/Page\b~', $response->getContent()));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(0, Document::count());
        $this->assertSame(0, Submission::count());
        $this->assertSame($authorization, $this->activity->fresh()->latestVersion->authorization);
        $this->assertDatabaseHas('audit_events', ['action' => 'annex_d.generated']);
        file_put_contents(storage_path('framework/testing/diagnostic-layout.pdf'), $response->getContent());
    }

    public function test_no_answer_and_remarks_are_preserved_without_automatic_recommendation(): void
    {
        $data = $this->activityData();
        $data['diagnostic']['answers']['minor_consent'] = ['answer' => 'no', 'remarks' => 'No participants below 18; confirm applicability with HEI.'];
        $data['diagnostic']['recommendation'] = 'revise';
        $activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $snapshot = app(DiagnosticBuilder::class)->snapshot($activity->latestVersion);
        $this->assertSame('no', $snapshot['answers']['minor_consent']['answer']);
        $this->assertSame('revise', $snapshot['recommendation']);
        $this->get(route('diagnostic.activity', $activity))->assertOk()->assertSee('No participants below 18')->assertSee('No');
        $this->get(route('diagnostic.activity', ['activity' => $activity->id, 'download' => 1]))->assertOk();
    }

    public function test_partial_diagnostic_is_saved_but_complete_pdf_requires_answers_and_signatories(): void
    {
        $data = $this->activityData();
        $data['diagnostic'] = ['answers' => ['course_alignment' => ['answer' => 'yes']]];
        $activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->get(route('diagnostic.activity', $activity))->assertOk()->assertSee('Complete these print fields');
        $this->get(route('diagnostic.activity', ['activity' => $activity->id, 'download' => 1]))->assertStatus(422);
    }

    public function test_invalid_diagnostic_answer_is_rejected_without_creating_a_revision(): void
    {
        $data = $this->activityData();
        $data['diagnostic']['answers']['minor_consent']['answer'] = 'na';
        try {
            app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
            $this->fail('Unsupported answer accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('diagnostic.answers.minor_consent.answer', $exception->errors());
        }
        $this->assertSame(1, $this->activity->versions()->count());
    }

    public function test_blank_optional_form_does_not_invent_an_assessment(): void
    {
        $data = $this->activityData();
        $data['diagnostic'] = ['answers' => ['course_alignment' => ['answer' => null, 'remarks' => '']],
            'recommendation' => null, 'signatories' => ['prepared_by' => ['name' => '', 'email' => null]]];
        $activity = app(SaveActivity::class)->execute($this->hei, $data);
        $this->assertNull($activity->latestVersion->diagnostic);
        $this->get(route('diagnostic.activity', $activity))->assertStatus(409);
    }

    public function test_livewire_activity_revision_exposes_diagnostic_inputs_and_preserves_old_version(): void
    {
        $old = $this->activity->latestVersion;
        Livewire::test(EditActivity::class, ['record' => $this->activity->id])
            ->fillForm(['diagnostic.answers.course_alignment.answer' => 'no', 'diagnostic.answers.course_alignment.remarks' => 'Revisit objectives.', 'diagnostic.recommendation' => 'revise'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('yes', $old->fresh()->diagnostic['answers']['course_alignment']['answer']);
        $this->assertSame('no', $this->activity->fresh()->latestVersion->diagnostic['answers']['course_alignment']['answer']);
        Livewire::test(ViewActivity::class, ['record' => $this->activity->id])->assertActionExists('diagnostic_preview');
    }

    public function test_omitted_diagnostic_on_revision_keeps_existing_assessment(): void
    {
        $old = $this->activity->latestVersion->diagnostic;
        $data = $this->activityData();
        unset($data['diagnostic']);
        $activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->assertSame($old, $activity->latestVersion->diagnostic);
    }

    public function test_submission_freezes_diagnostic_header_criteria_and_answers_across_corrections(): void
    {
        $submission = $this->submit();
        $oldId = $this->activity->latestVersion->id;
        $frozen = $submission->snapshot['annex_d'][$oldId];
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        app(ReviewSubmission::class)->execute($reviewer, $submission, 'corrections_requested', 'Revise the diagnostic.');
        $data = $this->activityData();
        $data['diagnostic']['answers']['course_alignment']['answer'] = 'no';
        $data['diagnostic']['recommendation'] = 'revise';
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->institution->update(['name' => 'Updated institution']);
        $revised = $this->submit($submission->id);
        $this->assertSame($frozen, $submission->fresh()->snapshot['annex_d'][$oldId]);
        $this->assertSame('no', $revised->snapshot['annex_d'][$this->activity->latestVersion->id]['answers']['course_alignment']['answer']);
        config(['diagnostic.groups' => ['Changed group' => ['changed' => 'Changed criterion']]]);
        $this->get(route('diagnostic.submission', ['publicId' => $submission->public_id]))->assertOk()->assertSee('Learning visit');
        $this->get(route('diagnostic.submission', ['publicId' => $submission->public_id, 'version' => $oldId]))->assertOk()
            ->assertSee('Diagnostic Test HEI')->assertDontSee('Updated institution')->assertDontSee('Changed criterion');
        $this->get(route('diagnostic.submission', ['publicId' => $submission->public_id, 'version' => $oldId, 'download' => 1]))->assertOk();
        $this->get(route('diagnostic.submission', ['publicId' => $submission->public_id, 'version' => 999999]))->assertNotFound();
    }

    public function test_legacy_submission_and_unprepared_activity_are_not_reconstructed(): void
    {
        $data = $this->activityData();
        unset($data['diagnostic']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data);
        $submission = $this->submit();
        $this->assertArrayNotHasKey('annex_d', $submission->snapshot);
        $this->get(route('diagnostic.activity', $this->activity))->assertStatus(409);
        $this->get(route('diagnostic.submission', ['publicId' => $submission->public_id]))->assertStatus(409);
    }

    public function test_scope_mfa_and_ched_jurisdiction_are_enforced(): void
    {
        $submission = $this->submit();
        $foreign = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $foreign->assignRole('hei_submitter');
        $this->actingAs($foreign)->get(route('diagnostic.activity', $this->activity))->assertForbidden();
        $this->get(route('diagnostic.submission', ['publicId' => $submission->public_id]))->assertForbidden();
        $reviewer = User::factory()->create(['region_id' => Region::create(['code' => 'III-D', 'name' => 'Other Region'])->id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer)->get(route('diagnostic.activity', $this->activity))->assertForbidden();
        $reviewer->forceFill(['region_id' => $this->institution->region_id])->save();
        $this->actingAs($reviewer->fresh())->get(route('diagnostic.activity', $this->activity))->assertOk();
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->actingAs($this->hei->fresh())->get(route('diagnostic.activity', $this->activity))->assertForbidden();
        auth()->logout();
        $this->get(route('diagnostic.activity', $this->activity))->assertRedirect();
    }

    public function test_letterhead_is_private_and_quarantine_or_changed_bytes_block_output(): void
    {
        $image = UploadedFile::fake()->image('letterhead.png', 600, 80);
        $bytes = file_get_contents($image->getRealPath());
        Storage::disk('private')->put('diagnostic-letterhead.png', $bytes);
        $document = Document::create(['institution_id' => $this->institution->id, 'document_type_id' => DocumentType::where('code', 'diagnostic')->firstOrFail()->id,
            'public_id' => (string) Str::uuid(), 'path' => 'diagnostic-letterhead.png', 'original_name' => 'letterhead.png', 'mime' => 'image/png',
            'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'confidentiality' => 'institutional', 'scan_status' => 'clean', 'is_signed' => false, 'uploaded_by' => $this->hei->id]);
        $data = $this->activityData();
        $data['diagnostic']['letterhead_document_id'] = $document->id;
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $url = route('diagnostic.activity', $this->activity);
        $this->get($url)->assertOk()->assertSee('data:image/png;base64,', false);
        $document->update(['scan_status' => 'pending']);
        $this->get($url)->assertStatus(409);
        $document->update(['scan_status' => 'clean']);
        Storage::disk('private')->put($document->path, 'changed bytes');
        $this->get($url)->assertStatus(409);
    }

    public function test_remarks_are_escaped_and_long_reports_paginate(): void
    {
        $data = $this->activityData();
        foreach ($data['diagnostic']['answers'] as &$answer) {
            $answer['remarks'] = str_repeat('Detailed fictional assessment. ', 25);
        }
        unset($answer);
        $data['diagnostic']['signatories']['prepared_by']['name'] = '<script>alert(1)</script>';
        $activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->get(route('diagnostic.activity', $activity))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
        $response = $this->get(route('diagnostic.activity', ['activity' => $activity->id, 'download' => 1]))->assertOk();
        $this->assertGreaterThan(1, preg_match_all('~/Type\s*/Page\b~', $response->getContent()));
        file_put_contents(storage_path('framework/testing/diagnostic-long-layout.pdf'), $response->getContent());
    }
}
