<?php

namespace Tests\Feature;

use App\Actions\Activities\SaveActivity;
use App\Actions\Activities\SaveSaaDraft;
use App\Actions\Submissions\ReviewSubmission;
use App\Actions\Submissions\SubmitCalendar;
use App\Filament\Hei\Resources\Pages\ListAccountabilityAffidavit;
use App\Filament\Hei\Resources\Pages\ViewAccountabilityAffidavit;
use App\Models\AcademicPeriod;
use App\Models\AccountabilityAffidavit;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Services\Compliance\RequirementResolver;
use App\Services\Reporting\SaaBuilder;
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

class SaaTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Institution $institution;

    private AcademicPeriod $period;

    private AccountabilityAffidavit $draft;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
        Storage::fake('private');
        config(['lakbay.allow_draft_policy' => true]);
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'SAA-TEST',
            'name' => 'SAA Test HEI', 'institution_type' => 'suc', 'address' => 'Calamba, Laguna']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Representative', 'verified_at' => now(), 'is_authorized' => true]);
        $this->period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => '2026-2027',
            'term' => 'First Semester', 'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->draft = app(SaveSaaDraft::class)->execute($this->hei, $this->data());
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function data(): array
    {
        return ['institution_id' => $this->institution->id, 'academic_period_id' => $this->period->id,
            'title' => 'First semester SAA', 'details' => ['name' => 'Fictional HEI President',
                'position' => 'President', 'citizenship' => 'Filipino']];
    }

    private function submit(?int $draftId, ?int $previousId = null): Submission
    {
        $calendar = Calendar::firstOrCreate(['institution_id' => $this->institution->id, 'title' => 'SAA Test Calendar'],
            ['academic_period_id' => $this->period->id, 'responsible_office' => 'OSAS', 'created_by' => $this->hei->id]);
        $activity = $calendar->activities()->first();
        if (! $activity) {
            $activity = app(SaveActivity::class)->execute($this->hei, [
                'institution_id' => $this->institution->id, 'academic_period_id' => $this->period->id, 'calendar_id' => $calendar->id,
                'title' => 'SAA Test Visit', 'category' => 1, 'academic_nature' => 'curricular', 'activity_type' => 'Visit',
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

        return app(SubmitCalendar::class)->execute($this->hei, $calendar, $policy->id, $evidence, $previousId, $draftId);
    }

    public function test_annex_c_mapping_reuses_identity_and_coverage_but_never_infers_execution(): void
    {
        $data = app(SaaBuilder::class)->snapshot($this->draft);
        $this->assertSame([], app(SaaBuilder::class)->missingFields($data));
        $this->assertSame('Fictional HEI President', $data['details']['name']);
        $this->assertSame('Calamba, Laguna', $data['details']['office_address']);
        $this->assertSame('First Semester', $data['details']['semester']);
        $this->assertSame('2026-2027', $data['details']['academic_year']);
        $this->assertSame('C', $data['template']['annex']);
        $this->assertSame(22, $data['template']['source_page']);
        $this->assertSame(1, $data['draft_revision']);
        $this->assertArrayNotHasKey('execution_date', $data['details']);
        $this->assertArrayNotHasKey('notarial_date', $data['details']);
    }

    public function test_printable_saa_preserves_undertakings_and_blank_notarial_fields(): void
    {
        $data = $this->data();
        $data['details'] += ['execution_place' => '', 'notarial_place' => ' ', 'doc_number' => ''];
        app(SaveSaaDraft::class)->execute($this->hei, $data, $this->draft);
        $this->get(route('saa.draft', $this->draft))->assertOk()
            ->assertSee("institution's authorized approval process", false)
            ->assertSee('safety, security, welfare, inclusivity, and accessibility')
            ->assertSee('full institutional responsibility and accountability')
            ->assertSee('HEAD OF HEI')->assertSee('SUBSCRIBED AND SWORN')
            ->assertSee('20____')->assertSee('Doc. No. ________')
            ->assertDontSee('First Semester Semester');
        $count = Document::count();
        $response = $this->get(route('saa.draft', ['affidavit' => $this->draft->id, 'download' => 1]));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame(1, preg_match_all('~/Type\s*/Page\b~', $response->getContent()));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame($count, Document::count());
        $this->assertSame(0, Submission::count());
        $this->assertSame(2, $this->draft->fresh()->revision);
        file_put_contents(storage_path('framework/testing/saa-layout.pdf'), $response->getContent());
    }

    public function test_unknown_affiant_fields_allow_draft_preview_but_not_pdf(): void
    {
        $draft = app(SaveSaaDraft::class)->execute($this->hei, array_replace($this->data(), ['details' => []]));
        $this->get(route('saa.draft', $draft))->assertOk()->assertSee('Complete these fields');
        $this->get(route('saa.draft', ['affidavit' => $draft->id, 'download' => 1]))->assertStatus(422);
    }

    public function test_saa_forms_create_edit_and_keep_separate_semester_drafts(): void
    {
        Livewire::test(ListAccountabilityAffidavit::class)->callAction('create', data: array_replace($this->data(),
            ['title' => 'Second semester SAA', 'details' => ['name' => 'Second Affiant', 'position' => 'President', 'semester' => 'Second']]))
            ->assertHasNoActionErrors();
        $this->assertSame(2, AccountabilityAffidavit::count());
        Livewire::test(ViewAccountabilityAffidavit::class, ['record' => $this->draft->id])
            ->callAction('saa_details', data: ['title' => 'Updated draft', 'details' => ['name' => 'Updated Affiant']])
            ->assertHasNoActionErrors()->assertActionExists('saa_preview');
        $this->assertSame('Updated Affiant', $this->draft->fresh()->details['name']);
        $this->assertSame(2, $this->draft->fresh()->revision);
    }

    public function test_snapshot_survives_draft_and_institution_changes_and_correction_omission(): void
    {
        $submission = $this->submit($this->draft->id);
        $snapshot = $submission->snapshot['annex_c'];
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id]);
        $reviewer->assignRole('ched_reviewer');
        app(ReviewSubmission::class)->execute($reviewer, $submission, 'corrections_requested', 'Update evidence.');
        app(SaveSaaDraft::class)->execute($this->hei, array_replace($this->data(), ['details' => ['name' => 'Different President', 'position' => 'President', 'citizenship' => 'Filipino']]), $this->draft);
        $this->institution->update(['name' => 'New institution name']);
        $revision = $this->submit(null, $submission->id);
        $this->assertSame($snapshot, $submission->fresh()->snapshot['annex_c']);
        $this->assertSame($snapshot, $revision->snapshot['annex_c']);
        $this->get(route('saa.submission', $submission->public_id))->assertOk()
            ->assertSee('Fictional HEI President')->assertSee('SAA Test HEI')->assertDontSee('Different President');
        $this->get(route('saa.submission', ['publicId' => $submission->public_id, 'download' => 1]))->assertOk();
    }

    public function test_selected_new_draft_replaces_only_the_new_submission_snapshot(): void
    {
        $submission = $this->submit($this->draft->id);
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id]);
        $reviewer->assignRole('ched_reviewer');
        app(ReviewSubmission::class)->execute($reviewer, $submission, 'corrections_requested', 'Revise SAA.');
        $data = $this->data();
        $data['details']['name'] = 'New President';
        app(SaveSaaDraft::class)->execute($this->hei, $data, $this->draft);
        $revision = $this->submit($this->draft->id, $submission->id);
        $this->assertSame('New President', $revision->snapshot['annex_c']['details']['name']);
        $this->assertSame('Fictional HEI President', $submission->fresh()->snapshot['annex_c']['details']['name']);
    }

    public function test_legacy_or_unselected_submission_never_reconstructs_current_saa(): void
    {
        $submission = $this->submit(null);
        $this->assertArrayNotHasKey('annex_c', $submission->snapshot);
        $this->get(route('saa.submission', $submission->public_id))->assertStatus(409);
    }

    public function test_incomplete_selected_draft_does_not_create_a_submission(): void
    {
        $data = $this->data();
        $data['details'] = [];
        $draft = app(SaveSaaDraft::class)->execute($this->hei, $data);
        try {
            $this->submit($draft->id);
            $this->fail('An incomplete selected draft was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('saa_draft_id', $exception->errors());
        }
        $this->assertSame(0, Submission::count());
    }

    public function test_saa_period_and_institution_are_checked_in_shared_actions(): void
    {
        $period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'Other year', 'starts_on' => '2027-11-01', 'ends_on' => '2028-10-31']);
        $data = $this->data();
        $data['academic_period_id'] = $period->id;
        $data['details']['semester'] = 'First';
        $draft = app(SaveSaaDraft::class)->execute($this->hei, $data);
        $this->expectException(HttpException::class);
        $this->submit($draft->id);
    }

    public function test_cross_institution_inactive_membership_and_other_region_cannot_access_saa(): void
    {
        $submission = $this->submit($this->draft->id);
        $other = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $other->assignRole('hei_submitter');
        $this->actingAs($other)->get(route('saa.draft', $this->draft))->assertForbidden();
        $this->get(route('saa.submission', $submission->public_id))->assertForbidden();
        $this->hei->institutions()->updateExistingPivot($this->institution->id, ['verified_at' => null]);
        $this->actingAs($this->hei)->get(route('saa.draft', $this->draft))->assertForbidden();
        $region = Region::create(['code' => 'III', 'name' => 'Other region']);
        $reviewer = User::factory()->create(['region_id' => $region->id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer)->get(route('saa.draft', $this->draft))->assertForbidden();
        $reviewer->forceFill(['region_id' => $this->institution->region_id])->save();
        $this->actingAs($reviewer->fresh())->get(route('saa.draft', $this->draft))->assertOk();
    }

    public function test_print_requires_login_and_mfa_and_ched_cannot_create_drafts(): void
    {
        auth()->logout();
        $this->get(route('saa.draft', $this->draft))->assertRedirect();
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->actingAs($this->hei->fresh())->get(route('saa.draft', $this->draft))->assertForbidden();
        $admin = User::factory()->create(['region_id' => $this->institution->region_id]);
        $admin->assignRole('system_administrator');
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        $this->actingAs($admin);
        Livewire::test(\App\Filament\Ched\Resources\Pages\ListAccountabilityAffidavit::class)->assertActionDoesNotExist('create');
    }

    public function test_optional_execution_and_notarial_fields_print_without_claiming_verified_notarization(): void
    {
        $data = $this->data();
        $data['details'] += ['execution_date' => '2026-10-12', 'execution_place' => 'Calamba',
            'notarial_date' => '2026-10-13', 'notarial_place' => 'Laguna', 'notary_name' => 'Fictional Notary',
            'identity_description' => 'Identity document description', 'doc_number' => '12', 'page_number' => '4', 'book_number' => '8', 'series' => '2026'];
        $draft = app(SaveSaaDraft::class)->execute($this->hei, $data);
        $this->get(route('saa.draft', $draft))->assertOk()->assertSee('October')->assertSee('Fictional Notary')->assertSee('Doc. No. 12');
        $this->get(route('saa.draft', ['affidavit' => $draft->id, 'download' => 1]))->assertOk();
        $this->assertDatabaseHas('audit_events', ['action' => 'annex_c.generated']);
        $this->assertSame(0, Document::count());
    }

    public function test_letterhead_quarantine_and_hash_protection_are_reused(): void
    {
        $image = UploadedFile::fake()->image('letterhead.png', 600, 100);
        $bytes = file_get_contents($image->getRealPath());
        Storage::disk('private')->put('saa-letterhead.png', $bytes);
        $document = Document::create(['institution_id' => $this->institution->id,
            'document_type_id' => DocumentType::where('code', 'saa')->firstOrFail()->id, 'public_id' => (string) Str::uuid(),
            'path' => 'saa-letterhead.png', 'original_name' => 'letterhead.png', 'mime' => 'image/png',
            'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'confidentiality' => 'institutional',
            'scan_status' => 'clean', 'is_signed' => false, 'uploaded_by' => $this->hei->id]);
        $data = $this->data();
        $data['details']['letterhead_document_id'] = $document->id;
        app(SaveSaaDraft::class)->execute($this->hei, $data, $this->draft);
        $url = route('saa.draft', $this->draft);
        $this->get($url)->assertOk()->assertSee('data:image/png;base64,', false);
        $document->update(['scan_status' => 'pending']);
        $this->get($url)->assertStatus(409);
        $document->update(['scan_status' => 'clean']);
        Storage::disk('private')->put($document->path, 'changed');
        $this->get($url)->assertStatus(409);
    }

    public function test_saa_factory_accepts_explicit_owned_institution_and_period(): void
    {
        $draft = AccountabilityAffidavit::factory()->for($this->institution)->for($this->period)->create();
        $this->assertSame($this->institution->id, $draft->institution_id);
        $this->assertSame($this->period->id, $draft->academic_period_id);
    }

    public function test_dynamic_fields_are_escaped(): void
    {
        $data = $this->data();
        $data['details']['name'] = '<script>alert(1)</script>';
        $draft = app(SaveSaaDraft::class)->execute($this->hei, $data);
        $this->get(route('saa.draft', $draft))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
    }
}
