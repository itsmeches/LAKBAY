<?php

namespace Tests\Feature;

use App\Actions\Activities\SaveActivity;
use App\Actions\Reports\SaveIncidentReportDraft;
use App\Actions\Reports\SubmitReport;
use App\Filament\Hei\Resources\Pages\ListIncidentReportDraft;
use App\Filament\Hei\Resources\Pages\ViewIncidentReportDraft;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\IncidentReport;
use App\Models\IncidentReportDraft;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\User;
use App\Services\Reporting\IncidentReportBuilder;
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

class IncidentReportTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Institution $institution;

    private Activity $activity;

    private IncidentReportDraft $draft;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-10-07 10:00', 'Asia/Manila'));
        Storage::fake('private');
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'INCIDENT-TEST',
            'name' => 'Incident Test HEI', 'institution_type' => 'suc', 'address' => 'Calamba, Laguna']);
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
        $this->draft = app(SaveIncidentReportDraft::class)->execute($this->hei, $this->data());
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function data(array $changes = []): array
    {
        return ['institution_id' => $this->institution->id, 'activity_version_id' => $this->activity->latestVersion->id,
            'title' => 'Incident preparation', 'details' => array_merge([
                'occurred_at' => '2026-10-01 12:00', 'location' => 'Exact incident location', 'type' => 'Transport delay',
                'description' => 'The bus experienced a delay.', 'immediate_actions' => 'Alternative transport was arranged.', 'current_status' => 'Resolved.',
                'persons' => [['name' => 'Fictional Person', 'role' => 'Participant', 'remarks' => 'Assisted']],
                'supporting_details' => 'Additional documents can follow.', 'official' => 'Fictional HEI Official', 'position' => 'President',
            ], $changes)];
    }

    private function document(array $changes = []): Document
    {
        return Document::create(array_merge(['institution_id' => $this->institution->id,
            'document_type_id' => DocumentType::where('code', 'incident')->firstOrFail()->id,
            'public_id' => (string) Str::uuid(), 'path' => 'fixtures/'.Str::uuid().'.pdf', 'original_name' => 'signed-incident.pdf',
            'mime' => 'application/pdf', 'size' => 9, 'sha256' => hash('sha256', '%PDF-test'),
            'confidentiality' => 'sensitive', 'scan_status' => 'clean', 'is_signed' => true, 'uploaded_by' => $this->hei->id], $changes));
    }

    private function file(?Document $document = null, bool $signed = true): IncidentReport
    {
        return app(SubmitReport::class)->execute($this->hei, 'incident', [
            'incident_report_draft_id' => $this->draft->id, 'document_id' => $signed ? ($document ?? $this->document())->id : null]);
    }

    public function test_template_maps_all_sections_and_does_not_create_receipt_on_preparation(): void
    {
        $data = app(IncidentReportBuilder::class)->snapshot($this->draft);
        $this->assertSame('H', $data['template']['annex']);
        $this->assertSame([28], $data['template']['source_pages']);
        $this->assertSame(22, $data['basic']['participants']);
        $this->assertStringContainsString('17:00', $data['basic']['proposed_dates']);
        $this->assertSame('2026-10-01 12:00', $data['details']['occurred_at']);
        $this->assertSame('Fictional Person', $data['details']['persons'][0]['name']);
        $this->assertSame([], app(IncidentReportBuilder::class)->missingFields($data));
        $this->assertDatabaseCount('incident_reports', 0);
        $this->assertNull($this->activity->fresh()->actual_ended_at);
        $this->get(route('incident.draft', $this->draft))->assertOk()->assertSee('PERSONS INVOLVED')
            ->assertSee('CURRENT STATUS')->assertSee('LIST OF SUPPORTING DOCUMENTS')->assertSee('CERTIFICATION');
        $pdf = $this->get(route('incident.draft', ['draft' => $this->draft->id, 'download' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('incident_reports', 0);
    }

    public function test_initial_notice_can_be_filed_without_signature_or_supporting_evidence(): void
    {
        $data = $this->data(['official' => null, 'position' => null, 'persons' => []]);
        app(SaveIncidentReportDraft::class)->execute($this->hei, $data, $this->draft);
        $this->get(route('incident.draft', $this->draft))->assertOk()->assertSee('Missing print fields');
        $this->get(route('incident.draft', ['draft' => $this->draft->id, 'download' => 1]))->assertStatus(422);
        $notice = $this->file(signed: false);
        $this->assertSame('initial_notice', $notice->intake_kind);
        $this->assertNull($notice->document_id);
        $this->assertTrue($notice->is_late);
        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], $notice->annex_h['details']['persons']);
        $this->assertNull($this->activity->fresh()->actual_ended_at);
    }

    public function test_cmo_forty_eight_hour_deadline_boundary_is_occurrence_based(): void
    {
        PolicyVersion::where('code', 'CMO24-2026')->firstOrFail()->update([
            'status' => 'confirmed', 'publication_on' => '2026-09-28', 'publication_reference' => 'TEST ONLY', 'effective_on' => '2026-10-13',
        ]);
        foreach (['2026-10-22 11:59:59' => false, '2026-10-22 12:00:00' => false, '2026-10-22 12:00:01' => true] as $time => $late) {
            $this->travelTo(CarbonImmutable::parse($time, 'Asia/Manila'));
            $draft = app(SaveIncidentReportDraft::class)->execute($this->hei, $this->data(['occurred_at' => '2026-10-20 12:00', 'official' => null]));
            $report = app(SubmitReport::class)->execute($this->hei, 'incident', ['incident_report_draft_id' => $draft->id]);
            $this->assertSame(48, $report->deadline_parameters['amount']);
            $this->assertTrue($report->occurred_at->addHours(48)->equalTo($report->due_at));
            $this->assertSame($late, $report->is_late);
            $this->assertFalse($report->provisional_deadline);
        }
    }

    public function test_signed_supplement_retains_root_deadline_and_original_persons(): void
    {
        $notice = $this->file(signed: false);
        $data = $this->data(['persons' => [['name' => 'Fictional Person', 'role' => 'Participant', 'remarks' => 'Follow-up completed']],
            'description' => 'Updated information']);
        $data['supplements_report_id'] = $notice->id;
        $draft = app(SaveIncidentReportDraft::class)->execute($this->hei, $data);
        $report = app(SubmitReport::class)->execute($this->hei, 'incident', ['incident_report_draft_id' => $draft->id, 'document_id' => $this->document()->id]);
        $this->assertSame('signed_report', $report->intake_kind);
        $this->assertSame($notice->id, $report->supplements_report_id);
        $this->assertTrue($notice->due_at->equalTo($report->due_at));
        $this->assertSame($notice->deadline_parameters, $report->deadline_parameters);
        $this->assertSame($notice->provisional_deadline, $report->provisional_deadline);
        $this->assertSame('Assisted', $notice->persons()->first()->remarks);
        $this->assertSame('Follow-up completed', $report->persons()->first()->remarks);
        $this->institution->update(['name' => 'Renamed institution']);
        $this->get(route('incident.report', $report))->assertOk()->assertSee('Incident Test HEI')->assertDontSee('Renamed institution');
    }

    public function test_supplement_cannot_reset_occurrence(): void
    {
        $notice = $this->file(signed: false);
        $data = $this->data(['occurred_at' => '2026-10-02 12:00']);
        $data['supplements_report_id'] = $notice->id;
        $this->expectException(ValidationException::class);
        app(SaveIncidentReportDraft::class)->execute($this->hei, $data);
    }

    public function test_initial_and_signed_filing_lock_preparation_and_prevent_duplicates(): void
    {
        $this->file(signed: false);
        try {
            app(SaveIncidentReportDraft::class)->execute($this->hei, $this->data(), $this->draft);
            $this->fail('Filed preparation was edited.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('title', $exception->errors());
        }
        try {
            $this->file();
            $this->fail('Preparation filed twice.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('incident_report_draft_id', $exception->errors());
        }
        $this->assertDatabaseCount('incident_reports', 1);
    }

    public function test_invalid_signed_originals_are_rejected(): void
    {
        foreach ([['scan_status' => 'pending'], ['is_signed' => false], ['mime' => 'image/png'], ['confidentiality' => 'institutional'],
            ['document_type_id' => DocumentType::where('code', 'completion')->firstOrFail()->id]] as $changes) {
            try {
                $this->file($this->document($changes));
                $this->fail('Invalid signed original was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('document', $exception->errors());
            }
        }
        $this->assertDatabaseCount('incident_reports', 0);
    }

    public function test_supporting_references_are_frozen_and_cross_institution_links_rejected(): void
    {
        $support = $this->document(['original_name' => 'support.pdf']);
        app(SaveIncidentReportDraft::class)->execute($this->hei, $this->data(['supporting_document_ids' => [$support->id]]), $this->draft);
        $report = $this->file();
        $this->document(['original_name' => 'replacement-support.pdf']);
        $this->assertSame($support->id, $report->annex_h['supporting_documents'][0]['id']);
        $this->assertSame($support->sha256, $report->annex_h['supporting_documents'][0]['sha256']);
        $this->get(route('incident.report', $report))->assertOk()->assertSee('support.pdf')->assertDontSee('replacement-support.pdf');
        $other = Institution::create(['region_id' => $this->institution->region_id, 'ched_code' => 'OTHER-H', 'name' => 'Other HEI', 'institution_type' => 'private']);
        $foreign = $this->document(['institution_id' => $other->id]);
        $this->expectException(HttpException::class);
        app(SaveIncidentReportDraft::class)->execute($this->hei, $this->data(['supporting_document_ids' => [$foreign->id]]));
    }

    public function test_quarantined_support_does_not_delay_initial_notice(): void
    {
        $support = $this->document();
        app(SaveIncidentReportDraft::class)->execute($this->hei, $this->data(['supporting_document_ids' => [$support->id]]), $this->draft);
        $support->update(['scan_status' => 'pending']);
        try {
            $this->file();
            $this->fail('Quarantined support accepted for signed filing.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('document', $exception->errors());
        }
        $notice = $this->file(signed: false);
        $this->assertSame('initial_notice', $notice->intake_kind);
    }

    public function test_incident_access_requires_ownership_or_regional_confidential_permission(): void
    {
        $report = $this->file();
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer)->get(route('incident.draft', $this->draft))->assertForbidden();
        $this->get(route('incident.report', $report))->assertForbidden();
        $reviewer->givePermissionTo('incidents.view');
        $this->get(route('incident.report', $report))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        Livewire::test(\App\Filament\Ched\Resources\Pages\ListIncidentReportDraft::class)->assertSuccessful()
            ->assertActionDoesNotExist('create')->assertCanSeeTableRecords([$this->draft]);
        Livewire::test(\App\Filament\Ched\Resources\Pages\ViewIncidentReportDraft::class, ['record' => $this->draft->id])
            ->assertActionHidden('incident_details')->assertActionHidden('incident_file');
        $outsider = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $outsider->assignRole('hei_submitter');
        $this->actingAs($outsider)->get(route('incident.report', ['report' => $report->id, 'download' => 1]))->assertForbidden();
        $this->expectException(HttpException::class);
        app(SaveIncidentReportDraft::class)->execute($outsider, $this->data());
    }

    public function test_mfa_is_required_and_legacy_reports_are_not_reconstructed(): void
    {
        $legacy = app(SubmitReport::class)->execute($this->hei, 'incident', ['activity_id' => $this->activity->id] + $this->data()['details']);
        $this->get(route('incident.report', $legacy))->assertStatus(409);
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->actingAs($this->hei->fresh())->get(route('incident.draft', $this->draft))->assertForbidden();
        auth()->logout();
        $this->get(route('incident.draft', $this->draft))->assertRedirect();
    }

    public function test_future_occurrence_is_rejected_and_entered_text_is_escaped(): void
    {
        app(SaveIncidentReportDraft::class)->execute($this->hei, $this->data(['description' => '<script>alert(1)</script>']), $this->draft);
        $this->get(route('incident.draft', $this->draft))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
        $this->expectException(ValidationException::class);
        app(SaveIncidentReportDraft::class)->execute($this->hei, $this->data(['occurred_at' => '2026-10-08 12:00']));
    }

    public function test_hei_interface_can_prepare_and_send_initial_notice(): void
    {
        Livewire::test(ListIncidentReportDraft::class)->assertSuccessful()->assertSee('New incident draft')
            ->callAction('create', $this->data(['official' => null]))->assertHasNoActionErrors();
        $this->assertDatabaseCount('incident_report_drafts', 2);
        Livewire::test(ViewIncidentReportDraft::class, ['record' => $this->draft->id])->assertSuccessful()
            ->assertSee('Preview / print incident')->callAction('incident_file', [])->assertHasNoActionErrors();
        $this->assertDatabaseCount('incident_reports', 1);
        Livewire::test(ViewIncidentReportDraft::class, ['record' => $this->draft->id])
            ->assertActionHidden('incident_details')->assertActionHidden('incident_file');
    }

    public function test_private_letterhead_rechecks_scan_status_and_actual_hash(): void
    {
        $image = UploadedFile::fake()->image('letterhead.png', 600, 80);
        $bytes = file_get_contents($image->getRealPath());
        Storage::disk('private')->put('incident-letterhead.png', $bytes);
        $document = $this->document(['path' => 'incident-letterhead.png', 'original_name' => 'letterhead.png',
            'mime' => 'image/png', 'confidentiality' => 'institutional', 'is_signed' => false,
            'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes)]);
        app(SaveIncidentReportDraft::class)->execute($this->hei, $this->data(['letterhead_document_id' => $document->id]), $this->draft);
        $url = route('incident.draft', $this->draft);
        $this->get($url)->assertOk()->assertSee('data:image/png;base64,', false);
        $document->update(['scan_status' => 'pending']);
        $this->get($url)->assertStatus(409);
        $document->update(['scan_status' => 'clean']);
        Storage::disk('private')->put($document->path, 'changed');
        $this->get($url)->assertStatus(409);
    }

    public function test_signed_filing_requires_print_fields_and_errors_are_visible_in_dialog(): void
    {
        app(SaveIncidentReportDraft::class)->execute($this->hei, $this->data(['official' => null]), $this->draft);
        Livewire::test(ViewIncidentReportDraft::class, ['record' => $this->draft->id])
            ->callAction('incident_file', ['document_id' => $this->document()->id])->assertHasActionErrors(['document_id']);
        $this->assertDatabaseCount('incident_reports', 0);
        $this->hei->institutions()->updateExistingPivot($this->institution->id, ['is_authorized' => false]);
        $this->expectException(HttpException::class);
        $this->file(signed: false);
    }

    public function test_ched_cannot_file_a_preparation_even_with_report_permission(): void
    {
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $reviewer->givePermissionTo(['incidents.view', 'reports.submit']);
        $this->expectException(HttpException::class);
        app(SubmitReport::class)->execute($reviewer, 'incident', ['incident_report_draft_id' => $this->draft->id]);
    }
}
