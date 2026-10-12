<?php

namespace Tests\Feature;

use App\Actions\Activities\SaveActivity;
use App\Actions\Activities\SaveCertificationDraft;
use App\Actions\Submissions\ReviewSubmission;
use App\Actions\Submissions\SubmitCalendar;
use App\Filament\Ched\Resources\Pages\ListCertificationDraft as ChedListCertificationDraft;
use App\Filament\Ched\Resources\Pages\ViewCertificationDraft as ChedViewCertificationDraft;
use App\Filament\Hei\Resources\Pages\ListCertificationDraft;
use App\Filament\Hei\Resources\Pages\ViewCertificationDraft;
use App\Models\AcademicPeriod;
use App\Models\Calendar;
use App\Models\CertificationDraft;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Services\Compliance\RequirementResolver;
use App\Services\Reporting\CertificationBuilder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CertificationTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Institution $institution;

    private AcademicPeriod $period;

    private CertificationDraft $draft;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
        Storage::fake('private');
        config(['lakbay.allow_draft_policy' => true]);
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'Certification-TEST',
            'name' => 'Certification Test HEI', 'institution_type' => 'suc', 'address' => 'Calamba, Laguna']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Representative', 'verified_at' => now(), 'is_authorized' => true]);
        $this->period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => '2026-2027',
            'term' => 'First Semester', 'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->draft = app(SaveCertificationDraft::class)->execute($this->hei, $this->data());
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function data(): array
    {
        $items = [];
        foreach (config('certification.rows') as $code => $row) {
            $items[$code] = ['status' => 'attested', 'supporting' => array_fill_keys(array_keys($row['supporting']), true)];
        }

        return ['institution_id' => $this->institution->id, 'academic_period_id' => $this->period->id,
            'title' => 'First semester certification', 'details' => ['official' => 'Fictional Authorized Official',
                'position' => 'President', 'items' => $items]];
    }

    private function submit(?int $draftId, ?int $previousId = null): Submission
    {
        $calendar = Calendar::firstOrCreate(['institution_id' => $this->institution->id, 'title' => 'Certification Test Calendar'],
            ['academic_period_id' => $this->period->id, 'responsible_office' => 'OSAS', 'created_by' => $this->hei->id]);
        $activity = $calendar->activities()->first();
        if (! $activity) {
            $activity = app(SaveActivity::class)->execute($this->hei, [
                'institution_id' => $this->institution->id, 'academic_period_id' => $this->period->id, 'calendar_id' => $calendar->id,
                'title' => 'Certification Test Visit', 'category' => 1, 'academic_nature' => 'curricular', 'activity_type' => 'Visit',
                'initiator' => 'hei', 'description' => 'Learn on site', 'learning_objectives' => 'Learn', 'responsible_office' => 'OSAS',
                'starts_at' => '2026-12-01 09:00', 'ends_at' => '2026-12-01 17:00', 'venue' => 'Laguna',
                'student_count' => 20, 'conditions' => [], 'authorization' => ['official' => 'President', 'date' => '2026-10-01'],
                'personnel' => [['role' => 'pic', 'name' => 'PIC', 'position' => 'Faculty', 'contact' => 'pic@example.test']],
            ]);
        }
        $version = $activity->latestVersion;
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

        return app(SubmitCalendar::class)->execute($this->hei, $calendar, $policy->id, $evidence, $previousId, certificationDraftId: $draftId);
    }

    public function test_annex_f_maps_all_fourteen_requirements_and_sixteen_supporting_checks(): void
    {
        $data = app(CertificationBuilder::class)->snapshot($this->draft);
        $this->assertCount(14, $data['details']['definition']['rows']);
        $this->assertSame(16, array_sum(array_map(fn ($row) => count($row['supporting']), $data['details']['definition']['rows'])));
        $this->assertSame('F', $data['template']['annex']);
        $this->assertSame([25, 26], $data['template']['source_pages']);
        $this->assertSame('Certification Test HEI', $data['header']['hei_name']);
        $this->assertSame('Calamba, Laguna', $data['header']['address']);
        $this->assertSame([], app(CertificationBuilder::class)->missingFields($data));
        $this->assertArrayNotHasKey('issued_on', $data['details']);
        $this->assertArrayNotHasKey('issued_at', $data['details']);
    }

    public function test_both_template_pages_print_all_text_without_creating_signed_evidence(): void
    {
        $preview = $this->get(route('certification.draft', $this->draft))->assertOk();
        foreach ($this->draft->details['definition']['rows'] as $row) {
            $preview->assertSee($row['requirement']);
            foreach ($row['supporting'] as $label) {
                $preview->assertSee($label);
            }
        }
        $preview->assertSee('Items a to c must be submitted immediately upon request of CHED')
            ->assertSee('Certified Correct:')->assertSee('Name and Signature of Authorized HEI Official')->assertSee('20____');
        $response = $this->get(route('certification.draft', ['draft' => $this->draft->id, 'download' => 1]));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        file_put_contents(storage_path('framework/testing/certification-layout.pdf'), $response->getContent());
        $this->assertSame(2, preg_match_all('~/Type\\s*/Page\\b~', $response->getContent()));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(0, Document::count());
        $this->assertSame(0, Submission::count());
        $this->assertSame(1, $this->draft->fresh()->revision);
        $this->assertDatabaseHas('audit_events', ['action' => 'annex_f.generated']);
    }

    public function test_pending_or_unchecked_supporting_items_remain_draft_only(): void
    {
        $data = $this->data();
        $data['details']['items']['orientation']['status'] = 'pending';
        $draft = app(SaveCertificationDraft::class)->execute($this->hei, $data);
        $this->get(route('certification.draft', $draft))->assertOk()->assertSee('attestation pending');
        $this->get(route('certification.draft', ['draft' => $draft->id, 'download' => 1]))->assertStatus(422);
        $data['details']['items']['orientation']['status'] = 'attested';
        $data['details']['items']['orientation']['supporting']['proof'] = false;
        $draft = app(SaveCertificationDraft::class)->execute($this->hei, $data, $draft);
        $this->get(route('certification.draft', $draft))->assertOk()->assertSee('availability not attested');
        $this->get(route('certification.draft', ['draft' => $draft->id, 'download' => 1]))->assertStatus(422);
    }

    public function test_not_applicable_reasons_are_retained_and_printed_as_additional_notes(): void
    {
        $data = $this->data();
        $data['details']['items']['costs'] = ['status' => 'not_applicable', 'reason' => 'No costs borne by students or parents.', 'supporting' => []];
        $draft = app(SaveCertificationDraft::class)->execute($this->hei, $data);
        $this->get(route('certification.draft', $draft))->assertOk()->assertSee('Annex F - Applicability notes')->assertSee('No costs borne by students or parents.');
        $response = $this->get(route('certification.draft', ['draft' => $draft->id, 'download' => 1]))->assertOk();
        $this->assertSame(3, preg_match_all('~/Type\\s*/Page\\b~', $response->getContent()));
        $this->assertSame([], app(CertificationBuilder::class)->missingFields(app(CertificationBuilder::class)->snapshot($draft)));
        file_put_contents(storage_path('framework/testing/certification-na-layout.pdf'), $response->getContent());
    }

    public function test_na_without_reason_or_with_checked_support_is_rejected(): void
    {
        $data = $this->data();
        $data['details']['items']['costs'] = ['status' => 'not_applicable', 'supporting' => []];
        try {
            app(SaveCertificationDraft::class)->execute($this->hei, $data);
            $this->fail('Unexplained N/A accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('details.items.costs.reason', $exception->errors());
        }
        $data['details']['items']['costs'] += ['reason' => 'No student costs.'];
        $data['details']['items']['costs']['supporting']['expenses'] = true;
        try {
            app(SaveCertificationDraft::class)->execute($this->hei, $data);
            $this->fail('Contradictory supporting checks accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('details.items.costs.status', $exception->errors());
        }
        $this->assertSame(1, CertificationDraft::count());
    }

    public function test_record_details_are_labeled_and_not_flattened_json_in_both_panels(): void
    {
        Livewire::test(ViewCertificationDraft::class, ['record' => $this->draft->id])
            ->assertSee('Official')->assertSee('Fictional Authorized Official')
            ->assertSee('Mission')
            ->assertSee('Aligned with the HEI mission-vision')
            ->assertDontSee('"mission":')->assertDontSee('"version":');

        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer);
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        Livewire::test(ChedViewCertificationDraft::class, ['record' => $this->draft->id])
            ->assertSee('Official')->assertSee('Aligned with the HEI mission-vision')
            ->assertDontSee('"mission":');
    }

    public function test_structured_record_values_escape_markup_and_preserve_false_and_blank_values(): void
    {
        $html = view('filament.infolists.structured-values', ['values' => [
            'supporting' => ['proof' => false, 'other' => null], 'remarks' => '<script>alert(1)</script>',
        ]])->render();

        $this->assertStringContainsString('Supporting', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('Not provided', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('<details>', $html);
        $this->assertStringContainsString('<summary', $html);
        $this->assertStringContainsString('>No</span>', $html);
    }

    public function test_forms_create_and_revise_certification_inputs(): void
    {
        Livewire::test(ListCertificationDraft::class)->callAction('create', data: array_replace($this->data(), ['title' => 'Second certification']))
            ->assertHasNoActionErrors();
        $this->assertSame(2, CertificationDraft::count());
        $data = $this->data();
        unset($data['institution_id'], $data['academic_period_id']);
        $data['details']['official'] = 'Changed Official';
        Livewire::test(ViewCertificationDraft::class, ['record' => $this->draft->id])->callAction('certification_details', data: $data)->assertHasNoActionErrors()
            ->assertActionExists('certification_preview');
        $this->assertSame('Changed Official', $this->draft->fresh()->details['official']);
        $this->assertSame(2, $this->draft->fresh()->revision);
    }

    public function test_submitted_certification_is_frozen_and_correction_without_replacement_keeps_it(): void
    {
        $submission = $this->submit($this->draft->id);
        $frozen = $submission->snapshot['annex_f'];
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        app(ReviewSubmission::class)->execute($reviewer, $submission, 'corrections_requested', 'Revise other package details.');
        $data = $this->data();
        $data['details']['official'] = 'Changed Official';
        app(SaveCertificationDraft::class)->execute($this->hei, $data, $this->draft);
        $this->institution->update(['name' => 'Changed institution', 'address' => 'Changed address']);
        $correction = $this->submit(null, $submission->id);
        $this->assertSame($frozen, $submission->fresh()->snapshot['annex_f']);
        $this->assertSame($frozen, $correction->snapshot['annex_f']);
        config(['certification.rows' => ['new' => ['requirement' => 'Changed requirement', 'supporting' => [], 'page' => 1]]]);
        $this->get(route('certification.submission', $submission->public_id))->assertOk()->assertSee('Fictional Authorized Official')
            ->assertSee('Certification Test HEI')->assertDontSee('Changed institution')->assertDontSee('Changed requirement');
        $this->get(route('certification.submission', ['publicId' => $submission->public_id, 'download' => 1]))->assertOk();
    }

    public function test_correction_explicitly_selected_revised_draft_creates_new_preserved_copy(): void
    {
        $submission = $this->submit($this->draft->id);
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id]);
        $reviewer->assignRole('ched_reviewer');
        app(ReviewSubmission::class)->execute($reviewer, $submission, 'corrections_requested', 'Change official particulars.');
        $data = $this->data();
        $data['details']['official'] = 'New Official';
        app(SaveCertificationDraft::class)->execute($this->hei, $data, $this->draft);
        $correction = $this->submit($this->draft->id, $submission->id);
        $this->assertSame('Fictional Authorized Official', $submission->fresh()->snapshot['annex_f']['details']['official']);
        $this->assertSame('New Official', $correction->snapshot['annex_f']['details']['official']);
    }

    public function test_legacy_and_unselected_submissions_do_not_invent_certification_data(): void
    {
        $submission = $this->submit(null);
        $this->assertArrayNotHasKey('annex_f', $submission->snapshot);
        $this->get(route('certification.submission', $submission->public_id))->assertStatus(409);
    }

    public function test_selected_incomplete_certification_does_not_replace_or_bypass_evidence(): void
    {
        $data = $this->data();
        $data['details']['items']['mission']['status'] = 'pending';
        app(SaveCertificationDraft::class)->execute($this->hei, $data, $this->draft);
        try {
            $this->submit($this->draft->id);
            $this->fail('Incomplete selected certification accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('certification_draft_id', $exception->errors());
        }
        $this->assertSame(0, Submission::count());
    }

    public function test_wrong_period_certification_selection_is_rejected(): void
    {
        $period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'Other period', 'starts_on' => '2027-11-01', 'ends_on' => '2028-10-31']);
        $data = $this->data();
        $data['academic_period_id'] = $period->id;
        $draft = app(SaveCertificationDraft::class)->execute($this->hei, $data);
        $this->expectException(HttpException::class);
        $this->submit($draft->id);
    }

    public function test_cross_institution_access_and_revoked_membership_are_rejected(): void
    {
        $submission = $this->submit($this->draft->id);
        $foreign = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $foreign->assignRole('hei_submitter');
        $this->actingAs($foreign)->get(route('certification.draft', ['draft' => $this->draft->id, 'download' => 1]))->assertForbidden();
        $this->get(route('certification.submission', $submission->public_id))->assertForbidden();
        $this->hei->institutions()->updateExistingPivot($this->institution->id, ['verified_at' => null]);
        $this->actingAs($this->hei)->get(route('certification.draft', $this->draft))->assertForbidden();
    }

    public function test_ched_jurisdiction_and_read_only_panel_are_enforced(): void
    {
        $otherRegion = Region::create(['code' => 'III-F', 'name' => 'Other Region']);
        $reviewer = User::factory()->create(['region_id' => $otherRegion->id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer)->get(route('certification.draft', $this->draft))->assertForbidden();
        $reviewer->forceFill(['region_id' => $this->institution->region_id])->save();
        $this->actingAs($reviewer->fresh())->get(route('certification.draft', $this->draft))->assertOk();
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        Livewire::test(ChedListCertificationDraft::class)->assertActionDoesNotExist('create');
        Livewire::test(ChedViewCertificationDraft::class, ['record' => $this->draft->id])
            ->assertActionHidden('certification_details');
    }

    public function test_mfa_and_authentication_are_required(): void
    {
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->actingAs($this->hei->fresh())->get(route('certification.draft', $this->draft))->assertForbidden();
        auth()->logout();
        $this->get(route('certification.draft', $this->draft))->assertRedirect();
    }

    public function test_optional_date_and_place_are_printed_without_verifying_execution(): void
    {
        $data = $this->data();
        $data['details'] += ['issued_on' => '2026-10-18', 'issued_at' => 'Calamba'];
        $draft = app(SaveCertificationDraft::class)->execute($this->hei, $data);
        $this->get(route('certification.draft', $draft))->assertOk()->assertSee('Issued this 18 day of October, 2026, at Calamba.');
        $this->get(route('certification.draft', ['draft' => $draft->id, 'download' => 1]))->assertOk();
        $this->assertSame(0, Document::count());
    }

    public function test_letterhead_quarantine_and_hash_checks_are_reused(): void
    {
        $image = UploadedFile::fake()->image('letterhead.png', 600, 80);
        $bytes = file_get_contents($image->getRealPath());
        Storage::disk('private')->put('certification-letterhead.png', $bytes);
        $document = Document::create(['institution_id' => $this->institution->id, 'document_type_id' => DocumentType::where('code', 'certification')->firstOrFail()->id,
            'public_id' => (string) Str::uuid(), 'path' => 'certification-letterhead.png', 'original_name' => 'letterhead.png', 'mime' => 'image/png',
            'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'confidentiality' => 'institutional', 'scan_status' => 'clean', 'is_signed' => false, 'uploaded_by' => $this->hei->id]);
        $data = $this->data();
        $data['details']['letterhead_document_id'] = $document->id;
        app(SaveCertificationDraft::class)->execute($this->hei, $data, $this->draft);
        $url = route('certification.draft', $this->draft);
        $this->get($url)->assertOk()->assertSee('data:image/png;base64,', false);
        $document->update(['scan_status' => 'pending']);
        $this->get($url)->assertStatus(409);
        $document->update(['scan_status' => 'clean']);
        Storage::disk('private')->put($document->path, 'changed');
        $this->get($url)->assertStatus(409);
    }

    public function test_dynamic_fields_are_escaped_and_factory_uses_explicit_ownership(): void
    {
        $data = $this->data();
        $data['details']['official'] = '<script>alert(1)</script>';
        $draft = app(SaveCertificationDraft::class)->execute($this->hei, $data);
        $this->get(route('certification.draft', $draft))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
        $factory = CertificationDraft::factory()->for($this->institution)->for($this->period)->create();
        $this->assertSame($this->institution->id, $factory->institution_id);
        $this->assertSame($this->period->id, $factory->academic_period_id);
    }
}
