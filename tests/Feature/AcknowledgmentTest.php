<?php

namespace Tests\Feature;

use App\Actions\Activities\SaveActivity;
use App\Actions\Submissions\IssueAcknowledgment;
use App\Actions\Submissions\ReviewSubmission;
use App\Actions\Submissions\SaveAcknowledgmentDraft;
use App\Actions\Submissions\SubmitCalendar;
use App\Filament\Ched\Resources\Pages\ListSubmission;
use App\Models\AcademicPeriod;
use App\Models\AcknowledgmentDraft;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Services\Compliance\RequirementResolver;
use App\Services\Reporting\AcknowledgmentBuilder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AcknowledgmentTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private User $staff;

    private Institution $institution;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
        Storage::fake('private');
        config(['lakbay.allow_draft_policy' => true]);
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'ANNEX-K',
            'name' => 'Fictional HEI', 'address' => 'Calamba, Laguna', 'institution_type' => 'private']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Representative', 'verified_at' => now(), 'is_authorized' => true]);
        $this->staff = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $this->staff->assignRole('ched_signatory');
        $period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'AY 2026-2027',
            'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $calendar = Calendar::create(['institution_id' => $this->institution->id, 'academic_period_id' => $period->id,
            'title' => 'Annual CLOCA', 'responsible_office' => 'Student Affairs', 'created_by' => $this->hei->id]);
        $activity = app(SaveActivity::class)->execute($this->hei, ['institution_id' => $this->institution->id,
            'academic_period_id' => $period->id, 'calendar_id' => $calendar->id, 'title' => 'Field learning', 'category' => 1,
            'academic_nature' => 'curricular', 'activity_type' => 'Field study', 'initiator' => 'hei', 'description' => 'Learning visit',
            'learning_objectives' => 'Observe practice', 'responsible_office' => 'Student Affairs', 'starts_at' => '2026-12-10 09:00',
            'ends_at' => '2026-12-10 17:00', 'venue' => 'Laguna', 'student_count' => 20, 'other_count' => 3, 'conditions' => [],
            'authorization' => ['official' => 'President', 'date' => '2026-09-01'],
            'personnel' => [['role' => 'pic', 'name' => 'Faculty Person', 'position' => 'Faculty', 'contact' => 'pic@example.test', 'first_aid_trained' => true]]]);
        $policy = PolicyVersion::first();
        $evidence = [];
        foreach (app(RequirementResolver::class)->resolve($activity->latestVersion, $policy) as $rule) {
            $evidence[$activity->latestVersion->id][$rule->id] = ['status' => 'submitted',
                'document_ids' => [$this->document($rule->requirementDefinition->code)->id]];
        }
        $this->submission = app(SubmitCalendar::class)->execute($this->hei, $calendar, $policy->id, $evidence);
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id]);
        $reviewer->assignRole('ched_reviewer');
        app(ReviewSubmission::class)->execute($reviewer, $this->submission, 'complete', 'Complete documents reviewed.');
        $this->submission->refresh();
        $this->actingAs($this->staff);
        Filament::setCurrentPanel(Filament::getPanel('ched'));
    }

    private function document(string $code): Document
    {
        $type = DocumentType::where('code', $code)->firstOrFail();
        $path = 'test/'.Str::uuid().'.pdf';
        Storage::disk('private')->put($path, '%PDF-test');

        return Document::create(['institution_id' => $this->institution->id, 'document_type_id' => $type->id,
            'public_id' => (string) Str::uuid(), 'disk' => 'private', 'path' => $path, 'original_name' => $code.'.pdf',
            'mime' => 'application/pdf', 'size' => 9, 'sha256' => hash('sha256', '%PDF-test'),
            'confidentiality' => $type->confidentiality, 'scan_status' => 'clean', 'is_signed' => true, 'uploaded_by' => $this->hei->id]);
    }

    private function fields(): array
    {
        return ['reference' => 'ACK-K-TEST', 'recipient' => 'Fictional President', 'recipient_position' => 'President',
            'address' => 'Calamba, Laguna', 'salutation' => 'President', 'signatory' => 'Fictional Director',
            'letter_date' => '2026-10-07', 'letterhead_text' => "COMMISSION ON HIGHER EDUCATION\nRegional Office IV-A",
            'originals_checked' => true];
    }

    private function prepare(): AcknowledgmentDraft
    {
        return app(SaveAcknowledgmentDraft::class)->execute($this->staff, $this->submission, $this->fields());
    }

    private function url(bool $issued = false, bool $download = false): string
    {
        return route('acknowledgment.print', ['publicId' => $this->submission->public_id, 'issued' => (int) $issued, 'download' => (int) $download]);
    }

    private function issue(): void
    {
        app(IssueAcknowledgment::class)->execute($this->staff, $this->submission,
            $this->fields() + ['document_id' => $this->document('acknowledgment')->id, 'use_annex_k' => true]);
    }

    public function test_preparation_printing_and_issuance_preserve_exact_revision_without_authorizing(): void
    {
        $snapshot = $this->submission->snapshot;
        $draft = $this->prepare();
        $this->assertSame(20, $draft->payload['programs'][0]['student_participations']);
        $this->assertSame(1, $draft->payload['programs'][0]['activity_count']);
        $this->assertSame($this->submission->received_at->toIso8601String(), $draft->payload['received_at']);
        $this->get($this->url())->assertOk()->assertSee('Fictional President')->assertSee('duly notarized')->assertSee('Unsigned');
        $pdf = $this->get($this->url(false, true))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        file_put_contents(storage_path('framework/testing/acknowledgment-layout.pdf'), $pdf->getContent());
        $this->assertSame('ready_for_acknowledgment', $this->submission->fresh()->status);
        $this->assertDatabaseCount('acknowledgments', 0);
        $this->issue();
        $ack = $this->submission->fresh()->acknowledgment;
        $this->assertSame($draft->payload, $ack->summary['annex_k']);
        $this->assertSame($snapshot, $this->submission->fresh()->snapshot);
        $this->institution->update(['name' => 'Later name']);
        $this->actingAs($this->hei)->get($this->url(true))->assertOk()->assertSee('Fictional HEI')->assertDontSee('Later name');
        $this->assertDatabaseHas('audit_events', ['action' => 'annex_k.prepared']);
    }

    public function test_missing_human_originals_confirmation_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(SaveAcknowledgmentDraft::class)->execute($this->staff, $this->submission, array_replace($this->fields(), ['originals_checked' => false]));
    }

    public function test_heis_cannot_prepare_or_retrieve_ched_drafts(): void
    {
        $this->prepare();
        $this->actingAs($this->hei)->get($this->url())->assertForbidden();
        $this->expectException(HttpException::class);
        app(SaveAcknowledgmentDraft::class)->execute($this->hei, $this->submission, $this->fields());
    }

    public function test_other_region_cannot_prepare_or_read(): void
    {
        $other = Region::create(['code' => 'OTHER', 'name' => 'Other region']);
        $this->staff->forceFill(['region_id' => $other->id])->save();
        $this->get($this->url())->assertForbidden();
        $this->expectException(HttpException::class);
        $this->prepare();
    }

    public function test_missing_mfa_cannot_read_letter(): void
    {
        $this->prepare();
        $this->staff->forceFill(['app_authentication_secret' => null])->save();
        $this->get($this->url())->assertForbidden();
    }

    public function test_quarantined_original_blocks_generation_and_issuance(): void
    {
        $this->prepare();
        Document::whereHas('documentType', fn ($query) => $query->where('code', 'saa'))->first()->update(['scan_status' => 'quarantined']);
        $this->get($this->url())->assertSessionHasErrors('originals_checked');
        $this->expectException(ValidationException::class);
        $this->issue();
    }

    public function test_tampered_original_blocks_preparation(): void
    {
        $original = Document::whereHas('documentType', fn ($query) => $query->where('code', 'cloca'))->first();
        Storage::disk('private')->put($original->path, 'tampered');
        $this->expectException(ValidationException::class);
        $this->prepare();
    }

    public function test_unreviewed_package_cannot_prepare(): void
    {
        $this->submission->update(['status' => 'under_review']);
        $this->get($this->url())->assertStatus(409);
        $this->expectException(ValidationException::class);
        $this->prepare();
    }

    public function test_issued_letter_prevents_preparation_edits(): void
    {
        $this->prepare();
        $this->issue();
        $this->get($this->url())->assertStatus(409);
        $this->expectException(ValidationException::class);
        $this->prepare();
    }

    public function test_revised_draft_replaces_only_working_preparation(): void
    {
        $draft = $this->prepare();
        $updated = app(SaveAcknowledgmentDraft::class)->execute($this->staff, $this->submission,
            array_replace($this->fields(), ['recipient' => 'Corrected president']));
        $this->assertSame($draft->id, $updated->id);
        $this->assertSame(2, $updated->revision);
        $this->assertDatabaseCount('acknowledgment_drafts', 1);
        $this->assertSame('ready_for_acknowledgment', $this->submission->fresh()->status);
    }

    public function test_issuance_rejects_mismatching_prepared_particulars(): void
    {
        $this->prepare();
        $this->expectException(ValidationException::class);
        app(IssueAcknowledgment::class)->execute($this->staff, $this->submission, array_replace($this->fields(),
            ['document_id' => $this->document('acknowledgment')->id, 'use_annex_k' => true, 'recipient' => 'Different president']));
    }

    public function test_legacy_signed_letter_is_available_without_fabricated_annex_k(): void
    {
        app(IssueAcknowledgment::class)->execute($this->staff, $this->submission,
            $this->fields() + ['document_id' => $this->document('acknowledgment')->id]);
        $this->get($this->url(true))->assertStatus(409);
        $this->assertNull($this->submission->fresh()->acknowledgment->summary['annex_k']);
    }

    public function test_filament_preparation_and_signed_record_workflow(): void
    {
        Livewire::test(ListSubmission::class)->callAction(TestAction::make('prepare_annex_k')->table($this->submission),
            data: $this->fields())->assertHasNoActionErrors();
        $this->assertDatabaseCount('acknowledgment_drafts', 1);
        Livewire::test(ListSubmission::class)->callAction(TestAction::make('acknowledge')->table($this->submission),
            data: $this->fields() + ['document_id' => $this->document('acknowledgment')->id, 'use_annex_k' => true])->assertHasNoActionErrors();
        $this->assertNotNull($this->submission->fresh()->acknowledgment->summary['annex_k']);
    }

    public function test_html_input_is_escaped_and_private(): void
    {
        app(SaveAcknowledgmentDraft::class)->execute($this->staff, $this->submission,
            array_replace($this->fields(), ['recipient' => '<script>alert(1)</script>']));
        $this->get($this->url())->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false)
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_missing_preparation_and_missing_originals_are_not_reconstructed(): void
    {
        $this->get($this->url())->assertStatus(409);
        $copy = clone $this->submission;
        $copy->snapshot = array_replace($copy->snapshot, ['documents' => []]);
        $this->expectException(ValidationException::class);
        app(AcknowledgmentBuilder::class)->verifyOriginals($this->staff, $copy);
    }

    public function test_non_calendar_package_cannot_prepare_annex_k(): void
    {
        $copy = $this->submission->replicate(['public_id', 'reference']);
        $copy->public_id = (string) Str::uuid();
        $copy->reference = 'ACTIVITY-ONLY';
        $copy->type = 'activity';
        $copy->calendar_id = null;
        $copy->save();
        $this->expectException(ValidationException::class);
        app(SaveAcknowledgmentDraft::class)->execute($this->staff, $copy, $this->fields());
    }

    public function test_other_institution_cannot_retrieve_issued_preparation(): void
    {
        $this->prepare();
        $this->issue();
        $other = Institution::create(['region_id' => $this->institution->region_id, 'ched_code' => 'OTHER-K',
            'name' => 'Other HEI', 'institution_type' => 'private']);
        $user = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $user->assignRole('hei_submitter');
        $user->institutions()->attach($other->id, ['position' => 'Representative', 'verified_at' => now(), 'is_authorized' => true]);
        $this->actingAs($user)->get($this->url(true))->assertForbidden();
    }

    public function test_long_program_summary_pdf_keeps_all_rows_and_signature(): void
    {
        $draft = $this->prepare();
        $data = $draft->payload;
        $data['programs'] = [];
        for ($i = 1; $i <= 40; $i++) {
            $data['programs'][] = ['program_course' => 'Fictional Program '.$i.' - Academic community engagement',
                'period_from' => '2026-12-10T01:00:00Z', 'period_until' => '2026-12-10T09:00:00Z',
                'activity_count' => 1, 'student_participations' => 20];
        }
        $draft->update(['payload' => $data]);
        $response = $this->get($this->url(false, true))->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        file_put_contents(storage_path('framework/testing/acknowledgment-long-layout.pdf'), $response->getContent());
    }

    public function test_program_groups_use_only_frozen_versions_and_student_counts(): void
    {
        $copy = clone $this->submission;
        $first = $copy->snapshot['activities'][0];
        $copy->snapshot = array_replace($copy->snapshot, ['activities' => [
            array_replace($first, ['program' => 'Engineering', 'student_count' => 20]),
            array_replace($first, ['program' => 'Engineering', 'student_count' => 15]),
            array_replace($first, ['program' => 'Education', 'student_count' => 8]),
        ]]);
        $data = app(AcknowledgmentBuilder::class)->snapshot($copy, $this->fields(), $this->staff, 1);
        $this->assertSame(2, $data['programs'][0]['activity_count']);
        $this->assertSame(35, $data['programs'][0]['student_participations']);
        $this->assertSame(8, $data['programs'][1]['student_participations']);
        $this->assertCount(1, $this->submission->snapshot['activities']);
    }
}
