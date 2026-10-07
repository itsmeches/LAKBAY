<?php

namespace Tests\Feature;

use App\Actions\Activities\SaveActivity;
use App\Actions\Activities\SaveClocaCalendar;
use App\Actions\Submissions\ReviewSubmission;
use App\Actions\Submissions\SubmitCalendar;
use App\Filament\Hei\Resources\Pages\EditActivity;
use App\Filament\Hei\Resources\Pages\ListCalendar;
use App\Filament\Hei\Resources\Pages\ViewCalendar;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Services\Compliance\RequirementResolver;
use App\Services\Reporting\ClocaBuilder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ClocaTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Institution $institution;

    private Calendar $calendar;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
        Storage::fake('private');
        config(['lakbay.allow_draft_policy' => true]);
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'CLOCA-TEST',
            'name' => 'Annex B Test HEI', 'address' => 'Calamba, Laguna', 'institution_type' => 'private']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Authorized representative', 'verified_at' => now(), 'is_authorized' => true]);
        $period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'AY 2026-2027',
            'term' => 'First semester', 'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->calendar = app(SaveClocaCalendar::class)->execute($this->hei, [
            'institution_id' => $this->institution->id, 'academic_period_id' => $period->id,
            'title' => 'Annual CLOCA', 'effective_on' => '2026-11-01', 'responsible_office' => 'Student Affairs',
            'annex_b' => ['prepared_by' => ['name' => 'Preparer Person', 'position' => 'Coordinator'],
                'reviewed_by' => ['name' => 'Reviewer Person', 'position' => 'Dean'],
                'noted_by' => ['name' => 'Noter Person', 'position' => 'President']],
        ]);
        $this->activity = app(SaveActivity::class)->execute($this->hei, $this->activityData());
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function activityData(): array
    {
        return ['institution_id' => $this->institution->id, 'academic_period_id' => $this->calendar->academic_period_id,
            'calendar_id' => $this->calendar->id, 'title' => 'Learning visit', 'category' => 1,
            'academic_nature' => 'curricular', 'activity_type' => 'Field study', 'initiator' => 'hei',
            'description' => 'Observe professional practice', 'learning_objectives' => 'Learn on site',
            'responsible_office' => 'Student Affairs', 'starts_at' => '2026-12-31 01:00', 'ends_at' => '2027-01-01 17:00',
            'venue' => 'Laguna and Quezon City', 'student_count' => 20, 'other_count' => 3, 'conditions' => [],
            'authorization' => ['official' => 'President', 'date' => '2026-10-01'],
            'personnel' => [['role' => 'pic', 'name' => 'PIC Person', 'position' => 'Faculty', 'contact' => 'pic@example.test'],
                ['role' => 'apic', 'name' => 'APIC Person', 'position' => 'Faculty', 'contact' => 'apic@example.test']],
            'cloca' => ['activity_type_code' => 'C', 'duration_days' => 2,
                'locations' => [['name' => 'Calamba', 'region_scope' => 'within'], ['name' => 'Quezon City', 'region_scope' => 'outside']],
                'agency_coordination' => 'NA - field study', 'requested_assistance' => 'NA - field study']];
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

        return app(SubmitCalendar::class)->execute($this->hei, $this->calendar->fresh(), $policy->id, $evidence, $previousId);
    }

    public function test_all_annex_b_columns_map_to_confirmed_activity_data(): void
    {
        $data = app(ClocaBuilder::class)->snapshot($this->calendar);
        $row = $data['rows'][0];
        $this->assertSame([], app(ClocaBuilder::class)->missingFields($data));
        $this->assertSame('Dec 2026 - Jan 2027', $row['month']);
        $this->assertSame(1, $row['category']);
        $this->assertSame('C', $row['type']);
        $this->assertSame(23, $row['participants']);
        $this->assertSame(2, $row['days']);
        $this->assertSame(2, $row['suggested_days']);
        $this->assertSame('PIC Person', $row['pic']);
        $this->assertSame('APIC Person', $row['apic']);
        $this->assertStringContainsString('within region', $row['locations']);
        $this->assertStringContainsString('outside region', $row['locations']);
        $this->assertSame('2026-11-01', $data['header']['effective_on']);
        $this->assertSame('AY 2026-2027', $data['header']['academic_year']);
        $this->assertSame('First semester', $data['header']['semester']);
        $this->assertNotNull($data['template']['id']);
    }

    public function test_preview_and_pdf_are_private_unsigned_and_do_not_create_evidence(): void
    {
        $documents = Document::count();
        $submissions = Submission::count();
        $this->get(route('cloca.calendar', $this->calendar))->assertOk()
            ->assertSee('Calamba (within region)')->assertSee('Quezon City (outside region)')
            ->assertSee('Download printable PDF')->assertSee('generated unsigned copy');
        $response = $this->get(route('cloca.calendar', ['calendar' => $this->calendar->id, 'download' => 1]));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame($documents, Document::count());
        $this->assertSame($submissions, Submission::count());
        $this->assertDatabaseHas('audit_events', ['action' => 'annex_b.generated', 'actor_id' => $this->hei->id]);
    }

    public function test_missing_print_fields_allow_preview_but_not_completed_pdf(): void
    {
        $this->calendar->update(['annex_b' => null, 'effective_on' => null]);
        $this->get(route('cloca.calendar', $this->calendar))->assertOk()->assertSee('Complete these print fields');
        $this->get(route('cloca.calendar', ['calendar' => $this->calendar->id, 'download' => 1]))->assertStatus(422);
    }

    public function test_calendar_forms_create_and_revise_annex_b_metadata(): void
    {
        Livewire::test(ListCalendar::class)->callAction('create', data: [
            'institution_id' => $this->institution->id, 'academic_period_id' => $this->calendar->academic_period_id,
            'title' => 'Second calendar', 'responsible_office' => 'Dean Office',
            'annex_b' => ['academic_year' => '2026-2027', 'prepared_by' => ['name' => 'Other Preparer', 'position' => 'Secretary']],
        ])->assertHasNoActionErrors();
        $this->assertSame('Other Preparer', Calendar::where('title', 'Second calendar')->firstOrFail()->annex_b['prepared_by']['name']);
        Livewire::test(ViewCalendar::class, ['record' => $this->calendar->id])
            ->callAction('cloca_details', data: ['title' => 'Updated calendar', 'responsible_office' => 'Updated Office',
                'effective_on' => '2026-11-02', 'annex_b' => ['semester' => 'Second semester']])
            ->assertHasNoActionErrors()->assertActionExists('cloca_preview');
        $this->assertSame('Second semester', $this->calendar->fresh()->annex_b['semester']);
    }

    public function test_activity_form_preserves_and_revises_cloca_fields(): void
    {
        Livewire::test(EditActivity::class, ['record' => $this->activity->id])
            ->assertFormSet(['cloca.activity_type_code' => 'C', 'cloca.duration_days' => 2])
            ->fillForm(['cloca.activity_type_code' => 'P', 'cloca.duration_days' => 3])->call('save')->assertHasNoFormErrors();
        $versions = $this->activity->versions()->orderBy('version')->get();
        $this->assertCount(2, $versions);
        $this->assertSame('C', $versions[0]->cloca['activity_type_code']);
        $this->assertSame('P', $versions[1]->cloca['activity_type_code']);
        $this->assertSame(3, (int) $versions[1]->cloca['duration_days']);
    }

    public function test_printed_submission_is_frozen_across_calendar_and_activity_edits_and_corrections(): void
    {
        $submission = $this->submit();
        $snapshot = $submission->snapshot;
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id]);
        $reviewer->assignRole('ched_reviewer');
        app(ReviewSubmission::class)->execute($reviewer, $submission, 'corrections_requested', 'Revise calendar.');
        app(SaveClocaCalendar::class)->execute($this->hei, ['title' => 'Changed', 'responsible_office' => 'New Office',
            'annex_b' => ['academic_year' => 'Changed year', 'prepared_by' => ['name' => 'New Person']]], $this->calendar);
        $this->institution->update(['name' => 'New HEI Name', 'address' => 'New Address']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, array_replace($this->activityData(), ['title' => 'New Activity']), $this->activity);
        $revised = $this->submit($submission->id);
        $this->assertSame($snapshot, $submission->fresh()->snapshot);
        $this->assertSame('Changed year', $revised->snapshot['annex_b']['header']['academic_year']);
        $this->assertSame('New HEI Name', $revised->snapshot['annex_b']['header']['hei_name']);
        $this->get(route('cloca.submission', $submission->public_id))->assertOk()
            ->assertSee('Annex B Test HEI')->assertSee('Preparer Person')->assertSee('Learning visit')
            ->assertDontSee('New HEI Name')->assertDontSee('New Activity');
    }

    public function test_legacy_submission_never_uses_current_data_to_invent_a_historical_form(): void
    {
        $submission = $this->submit();
        $snapshot = $submission->snapshot;
        unset($snapshot['annex_b']);
        DB::table('submissions')->where('id', $submission->id)->update(['snapshot' => json_encode($snapshot)]);
        $this->get(route('cloca.submission', $submission->public_id))->assertStatus(409);
    }

    public function test_cross_institution_revoked_membership_and_outside_region_cannot_print(): void
    {
        $other = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $other->assignRole('hei_submitter');
        $submission = $this->submit();
        $this->actingAs($other)->get(route('cloca.calendar', $this->calendar))->assertForbidden();
        $this->get(route('cloca.submission', $submission->public_id))->assertForbidden();
        $this->hei->institutions()->updateExistingPivot($this->institution->id, ['verified_at' => null]);
        $this->actingAs($this->hei)->get(route('cloca.calendar', $this->calendar))->assertForbidden();
        $region = Region::create(['code' => 'III', 'name' => 'Other region']);
        $reviewer = User::factory()->create(['region_id' => $region->id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer)->get(route('cloca.calendar', $this->calendar))->assertForbidden();
        $reviewer->forceFill(['region_id' => $this->institution->region_id])->save();
        $this->actingAs($reviewer->fresh())->get(route('cloca.calendar', $this->calendar))->assertOk();
    }

    public function test_print_routes_require_login_and_mfa(): void
    {
        auth()->logout();
        $this->get(route('cloca.calendar', $this->calendar))->assertRedirect();
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->actingAs($this->hei->fresh())->get(route('cloca.calendar', $this->calendar))->assertForbidden();
    }

    public function test_invalid_annex_activity_values_are_rejected_by_shared_action(): void
    {
        $data = $this->activityData();
        $data['cloca'] = ['activity_type_code' => 'invalid', 'duration_days' => 0,
            'locations' => [['name' => 'Location', 'region_scope' => 'unknown']]];
        try {
            app(SaveActivity::class)->execute($this->hei, $data);
            $this->fail('Invalid CLOCA values were accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cloca.activity_type_code', $exception->errors());
            $this->assertArrayHasKey('cloca.duration_days', $exception->errors());
            $this->assertArrayHasKey('cloca.locations.0.region_scope', $exception->errors());
        }
        $this->assertSame(1, $this->activity->versions()->count());
    }

    public function test_untrusted_text_is_escaped_in_preview(): void
    {
        $this->calendar->update(['responsible_office' => '<script>alert(1)</script>']);
        $this->get(route('cloca.calendar', $this->calendar))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>', false);
    }

    private function letterhead(?int $institutionId = null): Document
    {
        $file = UploadedFile::fake()->image('letterhead.png', 600, 100);
        $bytes = file_get_contents($file->getRealPath());
        Storage::disk('private')->put('fixtures/letterhead.png', $bytes);

        return Document::create(['institution_id' => $institutionId ?? $this->institution->id,
            'document_type_id' => DocumentType::where('code', 'cloca')->firstOrFail()->id,
            'public_id' => (string) Str::uuid(), 'path' => 'fixtures/letterhead.png', 'original_name' => 'letterhead.png',
            'mime' => 'image/png', 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes),
            'confidentiality' => 'institutional', 'scan_status' => 'clean', 'is_signed' => false, 'uploaded_by' => $this->hei->id]);
    }

    public function test_letterhead_stays_private_and_quarantine_and_hash_checks_are_enforced(): void
    {
        $image = $this->letterhead();
        $details = $this->calendar->annex_b;
        $details['letterhead_document_id'] = $image->id;
        app(SaveClocaCalendar::class)->execute($this->hei, ['title' => 'Calendar', 'responsible_office' => 'Office', 'annex_b' => $details], $this->calendar);
        $url = route('cloca.calendar', $this->calendar);
        $this->get($url)->assertOk()->assertSee('data:image/png;base64,', false);
        $image->update(['scan_status' => 'pending']);
        $this->get($url)->assertStatus(409);
        $image->update(['scan_status' => 'clean']);
        Storage::disk('private')->put($image->path, 'changed bytes');
        $this->get($url)->assertStatus(409);
    }

    public function test_letterhead_selector_cannot_attach_another_institutions_document(): void
    {
        $other = Institution::create(['region_id' => $this->institution->region_id, 'ched_code' => 'OTHER-CLOCA',
            'name' => 'Other HEI', 'institution_type' => 'suc']);
        $image = $this->letterhead($other->id);
        $this->expectException(HttpException::class);
        app(SaveClocaCalendar::class)->execute($this->hei, ['title' => 'Calendar', 'responsible_office' => 'Office',
            'annex_b' => ['letterhead_document_id' => $image->id]], $this->calendar);
    }

    public function test_large_calendar_has_repeated_pdf_headers_and_all_rows(): void
    {
        for ($index = 1; $index <= 36; $index++) {
            app(SaveActivity::class)->execute($this->hei, array_replace($this->activityData(), ['title' => 'Additional activity '.$index]));
        }
        $preview = $this->get(route('cloca.calendar', $this->calendar));
        $preview->assertOk()->assertSee('Additional activity 36')->assertSee('table-header-group', false);
        $response = $this->get(route('cloca.calendar', ['calendar' => $this->calendar->id, 'download' => 1]));
        $response->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        file_put_contents(storage_path('framework/testing/cloca-layout.pdf'), $response->getContent());
        $this->assertGreaterThan(1, preg_match_all('~/Type\s*/Page\b~', $response->getContent()));
    }
}
