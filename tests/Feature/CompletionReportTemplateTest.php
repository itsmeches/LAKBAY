<?php

namespace Tests\Feature;

use App\Actions\Activities\SaveActivity;
use App\Actions\Reports\SaveCompletionReportDraft;
use App\Actions\Reports\SubmitReport;
use App\Filament\Hei\Resources\Pages\ListCompletionReportDraft;
use App\Filament\Hei\Resources\Pages\ViewCompletionReportDraft;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\CompletionReport;
use App\Models\CompletionReportDraft;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\IncidentReport;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\User;
use App\Services\Reporting\CompletionReportBuilder;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CompletionReportTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Institution $institution;

    private Activity $activity;

    private CompletionReportDraft $draft;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-10-07 10:00', 'Asia/Manila'));
        Storage::fake('private');
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'ROC-TEST',
            'name' => 'ROC Test HEI', 'institution_type' => 'suc', 'address' => 'Calamba, Laguna']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Representative', 'verified_at' => now(), 'is_authorized' => true]);
        $period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => '2026-2027',
            'term' => 'First Semester', 'starts_on' => '2026-09-01', 'ends_on' => '2027-08-31']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, [
            'institution_id' => $this->institution->id, 'academic_period_id' => $period->id,
            'title' => 'Learning visit', 'category' => 2, 'academic_nature' => 'curricular', 'activity_type' => 'Visit',
            'initiator' => 'hei', 'description' => 'Learn on site', 'learning_objectives' => 'Learn', 'responsible_office' => 'OSAS',
            'starts_at' => '2026-10-01 09:00', 'ends_at' => '2026-10-01 17:00', 'venue' => 'Laguna',
            'student_count' => 20, 'other_count' => 2, 'conditions' => [],
            'authorization' => ['official' => 'President', 'date' => '2026-09-01'],
            'personnel' => [['role' => 'pic', 'name' => 'Faculty PIC', 'position' => 'Faculty', 'contact' => 'pic@example.test']],
        ]);
        $this->draft = app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data());
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function data(array $changes = []): array
    {
        return ['institution_id' => $this->institution->id, 'activity_version_id' => $this->activity->latestVersion->id,
            'title' => 'Learning visit ROC', 'details' => array_merge([
                'ended_at' => '2026-10-01 18:00', 'venue' => 'Actual Laguna venue', 'student_count' => 19, 'other_count' => 3,
                'implementation' => 'as_authorized', 'summary' => 'Students completed the visit and achieved the learning objectives.',
                'incidents_encountered' => false, 'incident_report_ids' => [], 'official' => 'Fictional HEI Official', 'position' => 'President',
            ], $changes)];
    }

    private function document(array $changes = []): Document
    {
        return Document::create(array_merge(['institution_id' => $this->institution->id,
            'document_type_id' => DocumentType::where('code', 'completion')->firstOrFail()->id,
            'public_id' => (string) Str::uuid(), 'path' => 'fixtures/'.Str::uuid().'.pdf', 'original_name' => 'signed-roc.pdf',
            'mime' => 'application/pdf', 'size' => 9, 'sha256' => hash('sha256', '%PDF-test'),
            'confidentiality' => 'institutional', 'scan_status' => 'clean', 'is_signed' => true, 'uploaded_by' => $this->hei->id], $changes));
    }

    private function file(?Document $document = null): CompletionReport
    {
        return app(SubmitReport::class)->execute($this->hei, 'completion', [
            'completion_report_draft_id' => $this->draft->id, 'document_id' => ($document ?? $this->document())->id]);
    }

    public function test_maps_template_without_submitting_or_recording_completion(): void
    {
        $data = app(CompletionReportBuilder::class)->snapshot($this->draft);
        $this->assertSame('G', $data['template']['annex']);
        $this->assertSame([27], $data['template']['source_pages']);
        $this->assertSame(22, $data['basic']['participants']);
        $this->assertSame('Actual Laguna venue', $data['basic']['venue']);
        $this->assertSame('Faculty PIC', $data['basic']['pic']);
        $this->assertSame('Not applicable', $data['basic']['apic']);
        $this->assertStringContainsString('17:00', $data['basic']['proposed_dates']);
        $this->assertSame('2026-10-01 18:00', $data['details']['ended_at']);
        $this->assertSame([], app(CompletionReportBuilder::class)->missingFields($data));
        $this->assertNull($this->activity->fresh()->actual_ended_at);
        $this->assertDatabaseCount('completion_reports', 0);
    }

    public function test_preview_and_pdf_are_private_unsigned_and_escaped(): void
    {
        app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data(['summary' => '<script>alert(1)</script>']), $this->draft);
        $this->get(route('roc.draft', $this->draft))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('SUMMARY OF IMPLEMENTATION')->assertSee('CERTIFICATION')
            ->assertHeader('Cache-Control', 'no-store, private');
        $pdf = $this->get(route('roc.draft', ['draft' => $this->draft->id, 'download' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('completion_reports', 0);
        $this->assertNull($this->activity->fresh()->actual_ended_at);
    }

    public function test_partial_drafts_preview_but_cannot_print_or_file(): void
    {
        $partial = app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data(['summary' => null, 'official' => null]));
        $this->get(route('roc.draft', $partial))->assertOk()->assertSee('Missing print fields');
        $this->get(route('roc.draft', ['draft' => $partial->id, 'download' => 1]))->assertStatus(422);
        $this->expectException(ValidationException::class);
        app(SubmitReport::class)->execute($this->hei, 'completion', ['completion_report_draft_id' => $partial->id, 'document_id' => $this->document()->id]);
    }

    public function test_changes_and_incident_explanations_are_required_for_printing(): void
    {
        $changed = app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data(['implementation' => 'with_changes', 'incidents_encountered' => true]));
        $missing = app(CompletionReportBuilder::class)->missingFields(app(CompletionReportBuilder::class)->snapshot($changed));
        $this->assertContains('Explanation of changes', $missing);
        $this->assertContains('Brief incident explanation', $missing);
        $this->get(route('roc.draft', ['draft' => $changed->id, 'download' => 1]))->assertStatus(422);
    }

    public function test_filing_preserves_data_actual_counts_and_seventy_two_hour_deadline(): void
    {
        $report = $this->file();
        $this->assertTrue($report->is_late);
        $this->assertTrue($report->ended_at->addHours(72)->equalTo($report->due_at));
        $this->assertSame(3, $report->other_count);
        $this->assertSame(19, $report->student_count);
        $this->assertSame($this->draft->id, $report->completion_report_draft_id);
        $this->assertSame($this->activity->latestVersion->id, $report->annex_g['activity_version_id']);
        $this->assertSame('completed', $this->activity->fresh()->execution_status);
        $this->institution->update(['name' => 'Changed HEI name', 'address' => 'Changed address']);
        $this->get(route('roc.report', $report))->assertOk()->assertSee('ROC Test HEI')->assertDontSee('Changed HEI name');
        $this->get(route('roc.report', ['report' => $report->id, 'download' => 1]))->assertOk();
        $this->assertDatabaseCount('completion_reports', 1);
    }

    public function test_filed_draft_cannot_be_edited_or_filed_twice(): void
    {
        $this->file();
        try {
            app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data(['summary' => 'Replacement']), $this->draft);
            $this->fail('Filed draft was editable.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('title', $exception->errors());
        }
        try {
            $this->file();
            $this->fail('Duplicate filing succeeded.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('completion_report_draft_id', $exception->errors());
        }
        $this->assertDatabaseCount('completion_reports', 1);
    }

    public function test_quarantined_unsigned_or_wrong_type_originals_cannot_be_filed(): void
    {
        foreach ([
            ['scan_status' => 'pending'], ['is_signed' => false], ['mime' => 'image/png'],
            ['document_type_id' => DocumentType::where('code', 'incident')->firstOrFail()->id],
        ] as $changes) {
            try {
                $this->file($this->document($changes));
                $this->fail('Invalid signed original accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('document', $exception->errors());
            }
        }
        $this->assertDatabaseCount('completion_reports', 0);
        $this->assertNull($this->activity->fresh()->actual_ended_at);
    }

    public function test_recorded_conclusion_cannot_be_overwritten_at_filing(): void
    {
        $this->activity->update(['actual_ended_at' => CarbonImmutable::parse('2026-10-01 17:30', 'Asia/Manila')]);
        $this->expectException(ValidationException::class);
        $this->file();
    }

    public function test_draft_cannot_use_future_actual_conclusion(): void
    {
        $this->expectException(ValidationException::class);
        app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data(['ended_at' => '2026-10-08 10:00']));
    }

    public function test_other_institution_cannot_read_prepare_or_file_the_draft(): void
    {
        $outsider = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $outsider->assignRole('hei_submitter');
        $this->actingAs($outsider)->get(route('roc.draft', $this->draft))->assertForbidden();
        $this->get(route('roc.draft', ['draft' => $this->draft->id, 'download' => 1]))->assertForbidden();
        foreach (['save', 'file'] as $operation) {
            try {
                if ($operation === 'save') {
                    app(SaveCompletionReportDraft::class)->execute($outsider, $this->data());
                } else {
                    app(SubmitReport::class)->execute($outsider, 'completion', ['completion_report_draft_id' => $this->draft->id]);
                }
                $this->fail('Outsider action succeeded.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_incident_drafts_require_confidential_permission_for_ched(): void
    {
        $incident = app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data(['incidents_encountered' => true, 'incident_explanation' => 'A minor delay was addressed.']));
        $ched = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $ched->assignRole('ched_reviewer');
        $this->actingAs($ched)->get(route('roc.draft', $this->draft))->assertOk();
        $this->get(route('roc.draft', $incident))->assertForbidden();
        $ched->givePermissionTo('incidents.view');
        $this->get(route('roc.draft', $incident))->assertOk()->assertSee('A minor delay was addressed.');
        $this->expectException(HttpException::class);
        app(SaveCompletionReportDraft::class)->execute($ched, $this->data());
    }

    public function test_incident_links_cannot_point_to_another_activity(): void
    {
        $foreignActivity = Activity::create(['institution_id' => $this->institution->id, 'academic_period_id' => $this->activity->academic_period_id,
            'public_id' => (string) Str::uuid(), 'title' => 'Another activity', 'created_by' => $this->hei->id]);
        $incident = IncidentReport::create(['institution_id' => $this->institution->id, 'activity_id' => $foreignActivity->id,
            'submitted_by' => $this->hei->id, 'occurred_at' => now()->subDay(), 'due_at' => now(), 'is_late' => false,
            'location' => 'Laguna', 'type' => 'Delay', 'description' => 'Delay', 'immediate_actions' => 'Assistance', 'current_status' => 'Resolved']);
        $this->expectException(HttpException::class);
        app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data(['incidents_encountered' => true, 'incident_report_ids' => [$incident->id]]));
    }

    public function test_legacy_report_is_not_reconstructed_from_current_data(): void
    {
        $legacy = app(SubmitReport::class)->execute($this->hei, 'completion', ['activity_id' => $this->activity->id, 'document_id' => $this->document()->id] + $this->data()['details']);
        $this->assertNull($legacy->annex_g);
        $this->get(route('roc.report', $legacy))->assertStatus(409);
    }

    public function test_cmo_incident_linking_is_enforced_when_filing_a_prepared_roc(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-30 10:00', 'Asia/Manila'));
        PolicyVersion::where('code', 'CMO24-2026')->firstOrFail()->update([
            'status' => 'confirmed', 'publication_on' => '2026-09-28', 'publication_reference' => 'TEST ONLY',
            'effective_on' => '2026-10-13',
        ]);
        $data = $this->data(['ended_at' => '2026-10-20 18:00', 'incidents_encountered' => true,
            'incident_explanation' => 'A transport delay was addressed.']);
        $draft = app(SaveCompletionReportDraft::class)->execute($this->hei, $data);
        try {
            app(SubmitReport::class)->execute($this->hei, 'completion', ['completion_report_draft_id' => $draft->id, 'document_id' => $this->document()->id]);
            $this->fail('CMO incident link was not required.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('incident_report_ids', $exception->errors());
        }
        $notice = app(SubmitReport::class)->execute($this->hei, 'incident', [
            'activity_id' => $this->activity->id, 'occurred_at' => '2026-10-20 12:00', 'location' => 'Laguna', 'type' => 'Delay',
            'description' => 'Protected incident narrative', 'immediate_actions' => 'Transport arranged', 'current_status' => 'Resolved',
        ]);
        $data['details']['incident_report_ids'] = [$notice->id];
        app(SaveCompletionReportDraft::class)->execute($this->hei, $data, $draft);
        $report = app(SubmitReport::class)->execute($this->hei, 'completion', [
            'completion_report_draft_id' => $draft->id, 'document_id' => $this->document()->id,
        ]);
        $this->assertSame([$notice->id], $report->incident_report_ids);
        $this->assertSame(72, $report->deadline_parameters['amount']);
        $this->get(route('roc.report', $report))->assertOk()->assertSee('A transport delay was addressed.')
            ->assertDontSee('Protected incident narrative');
    }

    public function test_letterhead_quarantine_and_hash_checks_apply_to_annex_g(): void
    {
        $image = UploadedFile::fake()->image('letterhead.png', 600, 80);
        $bytes = file_get_contents($image->getRealPath());
        Storage::disk('private')->put('roc-letterhead.png', $bytes);
        $document = $this->document(['path' => 'roc-letterhead.png', 'original_name' => 'letterhead.png', 'mime' => 'image/png',
            'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'is_signed' => false]);
        app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data(['letterhead_document_id' => $document->id]), $this->draft);
        $this->get(route('roc.draft', $this->draft))->assertOk()->assertSee('data:image/png;base64,', false);
        $document->update(['scan_status' => 'pending']);
        $this->get(route('roc.draft', $this->draft))->assertStatus(409);
        $document->update(['scan_status' => 'clean']);
        Storage::disk('private')->put($document->path, 'changed');
        $this->get(route('roc.draft', $this->draft))->assertStatus(409);
        $this->expectException(HttpException::class);
        $this->file();
    }

    public function test_ched_draft_list_filters_incident_narratives_and_has_no_write_actions(): void
    {
        $incident = app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data(['incidents_encountered' => true,
            'incident_explanation' => 'Restricted ROC details']));
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer);
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        Livewire::test(\App\Filament\Ched\Resources\Pages\ListCompletionReportDraft::class)
            ->assertActionDoesNotExist('create')->assertCanSeeTableRecords([$this->draft])->assertCanNotSeeTableRecords([$incident]);
        Livewire::test(\App\Filament\Ched\Resources\Pages\ViewCompletionReportDraft::class, ['record' => $this->draft->id])
            ->assertActionHidden('roc_details')->assertActionHidden('roc_file');
        Livewire::test(\App\Filament\Ched\Resources\Pages\ViewCompletionReportDraft::class, ['record' => $incident->id])->assertNotFound();
        $reviewer->forceFill(['region_id' => Region::firstOrCreate(['code' => 'OTHER', 'name' => 'Other region'])->id])->save();
        $this->actingAs($reviewer->fresh())->get(route('roc.draft', $this->draft))->assertForbidden();
    }

    public function test_mfa_and_guest_access_are_required(): void
    {
        auth()->logout();
        $this->get(route('roc.draft', $this->draft))->assertRedirect();
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->actingAs($this->hei->fresh())->get(route('roc.draft', $this->draft))->assertForbidden();
    }

    public function test_hei_pages_offer_preparation_printing_and_signed_filing(): void
    {
        Livewire::test(ListCompletionReportDraft::class)->assertSuccessful()->assertSee('New ROC draft')
            ->assertCanSeeTableRecords([$this->draft]);
        Livewire::test(ViewCompletionReportDraft::class, ['record' => $this->draft->id])->assertSuccessful()
            ->assertSee('Preview / print ROC')->assertSee('File signed ROC');
        Livewire::test(ListCompletionReportDraft::class)
            ->callAction('create', $this->data(['summary' => 'Created through the web form']))
            ->assertHasNoActionErrors();
        $this->assertDatabaseCount('completion_report_drafts', 2);
        $original = $this->document();
        Livewire::test(ViewCompletionReportDraft::class, ['record' => $this->draft->id])
            ->callAction('roc_file', ['document_id' => $original->id])->assertHasNoActionErrors();
        $this->assertDatabaseCount('completion_reports', 1);
        Livewire::test(ViewCompletionReportDraft::class, ['record' => $this->draft->id])
            ->assertActionHidden('roc_file')->assertActionHidden('roc_details');
    }

    public function test_filing_dialog_displays_domain_validation_errors(): void
    {
        $draft = app(SaveCompletionReportDraft::class)->execute($this->hei, $this->data(['summary' => null]));
        Livewire::test(ViewCompletionReportDraft::class, ['record' => $draft->id])
            ->callAction('roc_file', ['document_id' => $this->document()->id])
            ->assertHasActionErrors(['document_id']);
        $this->assertDatabaseCount('completion_reports', 0);
        $this->assertNull($this->activity->fresh()->actual_ended_at);
    }
}
