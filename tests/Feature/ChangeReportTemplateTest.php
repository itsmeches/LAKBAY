<?php

namespace Tests\Feature;

use App\Actions\Activities\SaveActivity;
use App\Actions\Reports\SaveChangeReportDraft;
use App\Actions\Reports\SubmitReport;
use App\Filament\Hei\Resources\Pages\ListChangeReportDraft;
use App\Filament\Hei\Resources\Pages\ViewChangeReportDraft;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\ActivityVersion;
use App\Models\ChangeReport;
use App\Models\ChangeReportDraft;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Services\Reporting\ChangeReportBuilder;
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

class ChangeReportTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Institution $institution;

    private Activity $activity;

    private ChangeReportDraft $draft;

    private ActivityVersion $old;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-10-07 10:00', 'Asia/Manila'));
        Storage::fake('private');
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'INCIDENT-TEST',
            'name' => 'Change Test HEI', 'institution_type' => 'suc', 'address' => 'Calamba, Laguna']);
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
        $this->old = $this->activity->latestVersion;
        $data = $this->old->only(['title', 'category', 'academic_nature', 'activity_type', 'initiator', 'description', 'learning_objectives', 'responsible_office', 'starts_at', 'ends_at', 'venue', 'student_count', 'other_count', 'conditions', 'authorization']);
        $data += ['institution_id' => $this->institution->id, 'academic_period_id' => $period->id];
        $data['venue'] = 'Batangas';
        $data['student_count'] = 25;
        $data['personnel'] = [['role' => 'pic', 'name' => 'Revised PIC', 'position' => 'Faculty', 'contact' => 'pic@example.test'],
            ['role' => 'apic', 'name' => 'Assistant PIC', 'position' => 'Faculty', 'contact' => 'apic@example.test']];
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->submission = Submission::create(['institution_id' => $this->institution->id, 'policy_version_id' => PolicyVersion::where('code', 'CMO24-2026')->firstOrFail()->id,
            'public_id' => (string) Str::uuid(), 'reference' => 'TEST-ORIGINAL', 'type' => 'activity', 'submitted_by' => $this->hei->id,
            'submitted_at' => CarbonImmutable::parse('2026-09-20 08:00', 'Asia/Manila')->utc(), 'received_at' => now(),
            'holiday_calendar_version' => 'TEST', 'snapshot' => []]);
        $this->submission->activityVersions()->attach($this->old->id);
        $this->draft = app(SaveChangeReportDraft::class)->execute($this->hei, $this->data());
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function data(array $changes = []): array
    {
        return ['institution_id' => $this->institution->id, 'original_version_id' => $this->old->id,
            'revised_version_id' => $this->activity->latestVersion->id, 'original_submission_id' => $this->submission->id,
            'title' => 'Change preparation', 'details' => array_merge(['changed_at' => '2026-10-01 12:00',
                'reason' => 'Venue changed because the original became unavailable.', 'supporting_details' => 'Updated venue correspondence.',
                'prepared_name' => 'Preparer', 'prepared_position' => 'Coordinator', 'prepared_contact' => '09123456789', 'prepared_email' => 'test@example.test',
                'reviewed_name' => 'Reviewer', 'reviewed_position' => 'Director', 'noted_name' => 'Noting official', 'noted_position' => 'President'], $changes)];
    }

    private function document(array $changes = []): Document
    {
        return Document::create(array_merge(['institution_id' => $this->institution->id,
            'document_type_id' => DocumentType::where('code', 'change')->firstOrFail()->id,
            'public_id' => (string) Str::uuid(), 'path' => 'fixtures/'.Str::uuid().'.pdf', 'original_name' => 'signed-change.pdf',
            'mime' => 'application/pdf', 'size' => 9, 'sha256' => hash('sha256', '%PDF-test'),
            'confidentiality' => 'institutional', 'scan_status' => 'clean', 'is_signed' => true, 'uploaded_by' => $this->hei->id], $changes));
    }

    private function file(?Document $document = null, bool $signed = true): ChangeReport
    {
        return app(SubmitReport::class)->execute($this->hei, 'change', [
            'change_report_draft_id' => $this->draft->id, 'document_id' => $signed ? ($document ?? $this->document())->id : null]);
    }

    public function test_annex_maps_comparison_source_date_and_signatories_without_creating_receipt(): void
    {
        $data = app(ChangeReportBuilder::class)->snapshot($this->draft);
        $this->assertSame('I', $data['template']['annex']);
        $this->assertSame([29], $data['template']['source_pages']);
        $this->assertSame('2026-09-20', $data['original_submission']['date']);
        $this->assertSame('electronic_receipt', $data['original_submission']['source']);
        $this->assertSame('Laguna', $data['rows'][1]['previous']);
        $this->assertSame('Batangas', $data['rows'][1]['revised']);
        $this->assertStringContainsString('Assistant PIC', $data['rows'][2]['revised']);
        $this->assertStringContainsString('27 total', $data['rows'][3]['revised']);
        $this->assertSame([], app(ChangeReportBuilder::class)->missingFields($data));
        $this->get(route('change.draft', $this->draft))->assertOk()->assertSee('PARTICULARS OF CHANGE')
            ->assertSee('REASON FOR CHANGE')->assertSee('LIST OF SUPPORTING DOCUMENTS')->assertSee('Reviewed by:')->assertSee('20 September 2026');
        $pdf = $this->get(route('change.draft', ['draft' => $this->draft->id, 'download' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertDatabaseCount('change_reports', 0);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_immediate_notice_does_not_require_print_fields_and_formal_deadline_is_unresolved(): void
    {
        app(SaveChangeReportDraft::class)->execute($this->hei, $this->data(['prepared_name' => null]), $this->draft);
        $this->get(route('change.draft', ['draft' => $this->draft->id, 'download' => 1]))->assertStatus(422);
        $report = $this->file(signed: false);
        $this->assertSame('immediate_notice', $report->intake_kind);
        $this->assertNull($report->document_id);
        $this->assertNull($report->due_at);
        $this->assertNull($report->is_late);
        $this->assertSame('unresolved', $report->deadline_parameters['status']);
        $this->assertNull($this->activity->fresh()->actual_ended_at);
    }

    public function test_external_reference_is_explicit_and_draft_date_is_not_used_as_submission(): void
    {
        $data = $this->data();
        $data['original_submission_id'] = null;
        $draft = app(SaveChangeReportDraft::class)->execute($this->hei, $data);
        $this->assertNull(app(ChangeReportBuilder::class)->snapshot($draft)['original_submission']);
        $this->get(route('change.draft', ['draft' => $draft->id, 'download' => 1]))->assertStatus(422);
        $data['details']['original_submitted_on'] = '2026-09-15';
        $data['details']['original_submission_reference'] = 'EXTERNAL-RECEIPT';
        $draft = app(SaveChangeReportDraft::class)->execute($this->hei, $data, $draft);
        $this->assertSame('hei_entered_external_reference', app(ChangeReportBuilder::class)->snapshot($draft)['original_submission']['source']);
        $this->get(route('change.draft', $draft))->assertOk()->assertSee('hei entered external reference')->assertSee('15 September 2026');
    }

    public function test_wrong_receipt_and_electronic_override_are_rejected(): void
    {
        $this->submission->activityVersions()->detach();
        try {
            app(SaveChangeReportDraft::class)->execute($this->hei, $this->data());
            $this->fail('Receipt not containing the original revision was accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        $this->submission->activityVersions()->attach($this->old->id);
        $this->expectException(ValidationException::class);
        app(SaveChangeReportDraft::class)->execute($this->hei, $this->data(['original_submitted_on' => '2026-09-21']));
    }

    public function test_signed_followup_preserves_original_notice_and_deadline(): void
    {
        $notice = $this->file(signed: false);
        $this->travelTo(now()->addDay());
        $data = $this->data(['reason' => 'Signed follow-up explanation']);
        $data['supplements_report_id'] = $notice->id;
        $draft = app(SaveChangeReportDraft::class)->execute($this->hei, $data);
        $report = app(SubmitReport::class)->execute($this->hei, 'change', ['change_report_draft_id' => $draft->id, 'document_id' => $this->document()->id]);
        $this->assertSame('signed_report', $report->intake_kind);
        $this->assertSame($notice->id, $report->supplements_report_id);
        $this->assertTrue($report->notified_at->equalTo($notice->notified_at));
        $this->assertSame($notice->deadline_parameters, $report->deadline_parameters);
        $this->assertNull($report->due_at);
        $this->assertNull($notice->fresh()->document_id);
        $this->assertSame('Venue changed because the original became unavailable.', $notice->annex_i['details']['reason']);
        $this->institution->update(['name' => 'New HEI name']);
        $this->get(route('change.report', $report))->assertOk()->assertSee('Change Test HEI')->assertDontSee('New HEI name');
        $this->expectException(ValidationException::class);
        app(SaveChangeReportDraft::class)->execute($this->hei, $this->data(['changed_at' => '2026-10-02 12:00']) + ['supplements_report_id' => $notice->id]);
    }

    public function test_filed_preparation_is_locked_and_duplicate_filing_rejected(): void
    {
        $report = $this->file();
        $this->assertSame('TEST-ORIGINAL', $report->original_submission_reference);
        try {
            app(SaveChangeReportDraft::class)->execute($this->hei, $this->data(), $this->draft);
            $this->fail('Filed preparation was edited.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('title', $exception->errors());
        }
        try {
            $this->file();
            $this->fail('Duplicate filing accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('change_report_draft_id', $exception->errors());
        }
        $this->assertDatabaseCount('change_reports', 1);
    }

    public function test_source_pair_cannot_be_changed_and_reverse_revisions_are_rejected(): void
    {
        $data = $this->data();
        [$data['original_version_id'], $data['revised_version_id']] = [$data['revised_version_id'], $data['original_version_id']];
        try {
            app(SaveChangeReportDraft::class)->execute($this->hei, $data);
            $this->fail('Reverse pair accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        $this->expectException(ValidationException::class);
        app(SaveChangeReportDraft::class)->execute($this->hei, $data, $this->draft);
    }

    public function test_invalid_signed_evidence_is_rejected(): void
    {
        foreach ([['scan_status' => 'pending'], ['mime' => 'image/png'], ['is_signed' => false], ['confidentiality' => 'sensitive'],
            ['document_type_id' => DocumentType::where('code', 'incident')->firstOrFail()->id]] as $changes) {
            try {
                $this->file($this->document($changes));
                $this->fail('Invalid signed evidence accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('document', $exception->errors());
            }
        }
        $this->assertDatabaseCount('change_reports', 0);
    }

    public function test_supporting_references_are_frozen_and_quarantine_does_not_delay_notice(): void
    {
        $support = $this->document(['original_name' => 'support.pdf']);
        app(SaveChangeReportDraft::class)->execute($this->hei, $this->data(['supporting_document_ids' => [$support->id]]), $this->draft);
        $support->update(['scan_status' => 'pending']);
        try {
            $this->file();
            $this->fail('Quarantined support accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('document', $exception->errors());
        }
        $notice = $this->file(signed: false);
        $this->assertSame($support->sha256, $notice->annex_i['supporting_documents'][0]['sha256']);
        $this->assertSame('support.pdf', $notice->annex_i['supporting_documents'][0]['name']);
    }

    public function test_other_particulars_are_escaped_and_future_change_rejected(): void
    {
        app(SaveChangeReportDraft::class)->execute($this->hei, $this->data(['other_changes' => [
            ['label' => 'Transport', 'previous' => 'Bus A', 'revised' => 'Bus B']], 'reason' => '<script>bad()</script>']), $this->draft);
        $this->get(route('change.draft', $this->draft))->assertOk()->assertSee('Other: Transport')->assertSee('Bus B')
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
        $this->expectException(ValidationException::class);
        app(SaveChangeReportDraft::class)->execute($this->hei, $this->data(['changed_at' => '2026-10-08 12:00']));
    }

    public function test_hei_interface_can_prepare_edit_and_file_with_visible_validation(): void
    {
        Livewire::test(ListChangeReportDraft::class)->assertSuccessful()->callAction('create', $this->data())->assertHasNoActionErrors();
        $this->assertDatabaseCount('change_report_drafts', 2);
        Livewire::test(ViewChangeReportDraft::class, ['record' => $this->draft->id])->callAction('change_details', [
            'title' => $this->draft->title, 'details' => $this->data(['prepared_name' => null])['details']])->assertHasNoActionErrors();
        Livewire::test(ViewChangeReportDraft::class, ['record' => $this->draft->id])->callAction('change_file', ['document_id' => $this->document()->id])->assertHasActionErrors(['document_id']);
        $this->assertDatabaseCount('change_reports', 0);
        Livewire::test(ViewChangeReportDraft::class, ['record' => $this->draft->id])->callAction('change_file', [])->assertHasNoActionErrors();
        Livewire::test(ViewChangeReportDraft::class, ['record' => $this->draft->id])->assertActionHidden('change_details')->assertActionHidden('change_file');
    }

    public function test_ched_read_access_cannot_write_and_other_institutions_cannot_read(): void
    {
        $report = $this->file();
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer)->get(route('change.report', $report))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        Livewire::test(\App\Filament\Ched\Resources\Pages\ListChangeReportDraft::class)->assertSuccessful()->assertActionDoesNotExist('create');
        Livewire::test(\App\Filament\Ched\Resources\Pages\ViewChangeReportDraft::class, ['record' => $this->draft->id])->assertActionHidden('change_details')->assertActionHidden('change_file');
        $reviewer->givePermissionTo('reports.submit');
        try {
            app(SubmitReport::class)->execute($reviewer, 'change', ['change_report_draft_id' => $this->draft->id]);
            $this->fail('CHED filed HEI preparation.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $outsider = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $outsider->assignRole('hei_submitter');
        $this->actingAs($outsider)->get(route('change.report', ['report' => $report->id, 'download' => 1]))->assertForbidden();
        $this->expectException(HttpException::class);
        app(SaveChangeReportDraft::class)->execute($outsider, $this->data());
    }

    public function test_signed_filing_requires_authorized_membership_and_mfa_for_print(): void
    {
        $this->hei->institutions()->updateExistingPivot($this->institution->id, ['is_authorized' => false]);
        try {
            $this->file();
            $this->fail('Unauthorized member filed report.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->actingAs($this->hei->fresh())->get(route('change.draft', $this->draft))->assertForbidden();
    }

    public function test_historical_reports_are_not_reconstructed(): void
    {
        $legacy = app(SubmitReport::class)->execute($this->hei, 'change', ['activity_id' => $this->activity->id,
            'original_version_id' => $this->old->id, 'revised_version_id' => $this->activity->latestVersion->id,
            'changed_at' => '2026-10-01 12:00', 'reason' => 'Legacy record']);
        $this->get(route('change.report', $legacy))->assertStatus(409);
    }

    public function test_letterhead_rechecks_quarantine_and_actual_bytes(): void
    {
        $image = UploadedFile::fake()->image('letterhead.png', 600, 80);
        $bytes = file_get_contents($image->getRealPath());
        Storage::disk('private')->put('change-letterhead.png', $bytes);
        $document = $this->document(['path' => 'change-letterhead.png', 'original_name' => 'letterhead.png',
            'mime' => 'image/png', 'is_signed' => false, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes)]);
        app(SaveChangeReportDraft::class)->execute($this->hei, $this->data(['letterhead_document_id' => $document->id]), $this->draft);
        $url = route('change.draft', $this->draft);
        $this->get($url)->assertOk()->assertSee('data:image/png;base64,', false);
        $document->update(['scan_status' => 'pending']);
        $this->get($url)->assertStatus(409);
        $document->update(['scan_status' => 'clean']);
        Storage::disk('private')->put($document->path, 'changed bytes');
        $this->get($url)->assertStatus(409);
    }

    public function test_cross_institution_and_confidential_support_cannot_be_linked(): void
    {
        $other = Institution::create(['region_id' => $this->institution->region_id, 'ched_code' => 'OTHER-I', 'name' => 'Other HEI', 'institution_type' => 'private']);
        foreach ([['institution_id' => $other->id], ['confidentiality' => 'sensitive']] as $changes) {
            $document = $this->document($changes);
            try {
                app(SaveChangeReportDraft::class)->execute($this->hei, $this->data(['supporting_document_ids' => [$document->id]]));
                $this->fail('Inaccessible supporting evidence was linked.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
        $this->assertDatabaseCount('change_report_drafts', 1);
    }

    public function test_jurisdiction_and_verified_membership_are_required(): void
    {
        $otherRegion = Region::create(['code' => 'OTHER-I', 'name' => 'Other region']);
        $reviewer = User::factory()->create(['region_id' => $otherRegion->id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer)->get(route('change.draft', $this->draft))->assertForbidden();
        $this->hei->institutions()->updateExistingPivot($this->institution->id, ['verified_at' => null]);
        $this->actingAs($this->hei)->get(route('change.draft', $this->draft))->assertForbidden();
        $this->expectException(HttpException::class);
        app(SaveChangeReportDraft::class)->execute($this->hei, $this->data());
    }

    public function test_legacy_deadline_remains_policy_versioned(): void
    {
        $submission = $this->submission->replicate();
        $submission->forceFill(['public_id' => (string) Str::uuid(), 'reference' => 'TEST-JULY',
            'policy_version_id' => PolicyVersion::where('code', 'LAKBAY-JULY-2026-DRAFT')->firstOrFail()->id])->save();
        $submission->activityVersions()->attach($this->old->id);
        $report = $this->file();
        $this->assertSame('legacy_provisional', $report->deadline_parameters['status']);
        $this->assertTrue($report->changed_at->addHours(72)->equalTo($report->due_at));
        $this->assertTrue($report->is_late);
    }

    public function test_itinerary_changes_are_preserved_in_manila_time(): void
    {
        $this->activity->latestVersion->itineraryItems()->create(['sequence' => 1,
            'starts_at' => CarbonImmutable::parse('2026-10-01 10:00', 'Asia/Manila')->utc(),
            'description' => 'Museum visit', 'location' => 'Batangas museum', 'responsible_person' => 'Revised PIC']);
        $draft = app(SaveChangeReportDraft::class)->execute($this->hei, $this->data());
        $rows = app(ChangeReportBuilder::class)->snapshot($draft)['rows'];
        $row = collect($rows)->firstWhere('label', 'Other: Itinerary');
        $this->assertSame('Not recorded', $row['previous']);
        $this->assertStringContainsString('01 Oct 2026 10:00 (Asia/Manila)', $row['revised']);
        $this->assertStringContainsString('Batangas museum', $row['revised']);
        $this->assertStringNotContainsString('starts_at', $row['revised']);
        $this->assertFalse(collect($this->draft->context['rows'])->contains('label', 'Other: Itinerary'));
    }
}
