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
use App\Services\Reporting\ItineraryBuilder;
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

class ItineraryTest extends TestCase
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
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'ITINERARY-TEST',
            'name' => 'Itinerary Test HEI', 'address' => 'Calamba, Laguna', 'institution_type' => 'private']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Authorized representative', 'verified_at' => now(), 'is_authorized' => true]);
        $this->period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'AY 2026-2027',
            'term' => 'First semester', 'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, $this->activityData());
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function itineraryData(): array
    {
        return ['signatories' => [
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
            'annex_j' => $this->itineraryData(),
            'itinerary' => [
                ['starts_at' => '2026-12-31 01:30', 'description' => 'Departure and briefing', 'location' => 'Calamba', 'responsible_person' => 'PIC Person'],
                ['starts_at' => '2026-12-31 09:00', 'description' => 'Learning visit', 'location' => 'Quezon City venue', 'dot_accreditation' => 'DOT-TEST-REFERENCE', 'responsible_person' => 'APIC Person'],
            ]];
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

    public function test_template_maps_basic_information_six_columns_and_signatories(): void
    {
        $data = app(ItineraryBuilder::class)->snapshot($this->activity->latestVersion);
        $this->assertSame('J', $data['template']['annex']);
        $this->assertSame(30, $data['template']['source_page']);
        $this->assertSame(23, $data['basic']['participants']);
        $this->assertSame('APIC Person', $data['basic']['apic']);
        $this->assertSame('31 Dec 2026', $data['rows'][0]['date']);
        $this->assertSame('01:30', $data['rows'][0]['time']);
        $this->assertSame('2026-12-30 17:30', $this->activity->latestVersion->itineraryItems()->first()->starts_at->format('Y-m-d H:i'));
        $this->assertSame('DOT-TEST-REFERENCE', $data['rows'][1]['dot_accreditation']);
        $this->assertSame([], app(ItineraryBuilder::class)->missingFields($data));
        $this->get(route('itinerary.activity', $this->activity))->assertOk()->assertSee('DETAILED ITINERARY')
            ->assertSee('Information on DOT Accreditation')->assertSee('Person/s Responsible')->assertSee('Fictional Reviewer');
    }

    public function test_pdf_is_unsigned_and_does_not_create_evidence_or_submission(): void
    {
        $authorization = $this->activity->latestVersion->authorization;
        $response = $this->get(route('itinerary.activity', ['activity' => $this->activity->id, 'download' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame(1, preg_match_all('~/Type\s*/Page\b~', $response->getContent()));
        file_put_contents(storage_path('framework/testing/itinerary-layout.pdf'), $response->getContent());
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('submissions', 0);
        $this->assertSame($authorization, $this->activity->fresh()->latestVersion->authorization);
        $this->assertDatabaseHas('audit_events', ['action' => 'annex_j.generated']);
    }

    public function test_partial_preparation_is_previewable_but_cannot_be_printed(): void
    {
        $data = $this->activityData();
        $data['annex_j'] = ['signatories' => ['prepared_by' => ['name' => 'Preparer']]];
        $data['itinerary'] = [];
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->get(route('itinerary.activity', $this->activity))->assertOk()->assertSee('At least one itinerary row')->assertSee('Complete these print fields');
        $this->get(route('itinerary.activity', ['activity' => $this->activity->id, 'download' => 1]))->assertStatus(422);
    }

    public function test_invalid_rows_are_rejected_before_creating_revision(): void
    {
        foreach ([['starts_at' => 'invalid-date'], ['responsible_person' => ''], ['activity_version_id' => 12345], ['description' => str_repeat('x', 1001)]] as $bad) {
            $data = $this->activityData();
            $data['itinerary'][0] = array_merge($data['itinerary'][0], $bad);
            try {
                app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
                $this->fail('Invalid itinerary accepted.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
        $this->assertSame(1, $this->activity->versions()->count());
    }

    public function test_omitted_rows_and_metadata_are_preserved_but_explicit_clear_is_supported(): void
    {
        $old = $this->activity->latestVersion;
        $data = $this->activityData();
        unset($data['itinerary'], $data['annex_j']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->assertSame($old->annex_j, $this->activity->latestVersion->annex_j);
        $this->assertSame(2, $this->activity->latestVersion->itineraryItems()->count());
        $this->assertTrue($old->itineraryItems()->first()->starts_at->equalTo($this->activity->latestVersion->itineraryItems()->first()->starts_at));
        $data['itinerary'] = [];
        $data['annex_j'] = null;
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->assertNull(app(ItineraryBuilder::class)->snapshot($this->activity->latestVersion));
        $this->assertSame(2, $old->itineraryItems()->count());
    }

    public function test_reordering_rows_assigns_consistent_sequence_without_overwriting_originals(): void
    {
        $old = $this->activity->latestVersion;
        $data = $this->activityData();
        $data['itinerary'] = array_reverse($data['itinerary']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $rows = $this->activity->latestVersion->itineraryItems()->orderBy('sequence')->get();
        $this->assertSame([1, 2], $rows->pluck('sequence')->all());
        $this->assertSame('Learning visit', $rows[0]->description);
        $this->assertSame('Departure and briefing', $old->itineraryItems()->first()->description);
    }

    public function test_livewire_revision_prefills_manila_times_and_preserves_prior_rows(): void
    {
        $old = $this->activity->latestVersion;
        $component = Livewire::test(EditActivity::class, ['record' => $this->activity->id])->assertSuccessful();
        $rows = array_values($component->get('data.itinerary'));
        $this->assertSame('2026-12-31 01:30:00', $rows[0]['starts_at']);
        $rows[0]['description'] = 'Updated briefing';
        $component->fillForm(['itinerary' => $rows, 'annex_j.signatories.prepared_by.name' => 'Updated Preparer'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Departure and briefing', $old->itineraryItems()->first()->description);
        $this->assertSame('Updated briefing', $this->activity->fresh()->latestVersion->itineraryItems()->first()->description);
        $this->assertSame('Updated Preparer', $this->activity->fresh()->latestVersion->annex_j['signatories']['prepared_by']['name']);
        Livewire::test(ViewActivity::class, ['record' => $this->activity->id])->assertActionExists('itinerary_preview');
    }

    public function test_submission_freezes_itinerary_and_header_across_correction(): void
    {
        $submission = $this->submit();
        $oldId = $this->activity->latestVersion->id;
        $frozen = $submission->snapshot['annex_j'][$oldId];
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        app(ReviewSubmission::class)->execute($reviewer, $submission, 'corrections_requested', 'Revise the itinerary.');
        $data = $this->activityData();
        $data['itinerary'][0]['description'] = 'Corrected briefing';
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->institution->update(['name' => 'Updated institution']);
        $revised = $this->submit($submission->id);
        $this->assertSame($frozen, $submission->fresh()->snapshot['annex_j'][$oldId]);
        $this->assertSame('Corrected briefing', $revised->snapshot['annex_j'][$this->activity->latestVersion->id]['rows'][0]['activity']);
        $this->get(route('itinerary.submission', ['publicId' => $submission->public_id]))->assertOk()->assertSee('Learning visit');
        $this->get(route('itinerary.submission', ['publicId' => $submission->public_id, 'version' => $oldId]))->assertOk()
            ->assertSee('Itinerary Test HEI')->assertSee('Departure and briefing')->assertDontSee('Updated institution')->assertDontSee('Corrected briefing');
        $this->get(route('itinerary.submission', ['publicId' => $submission->public_id, 'version' => 999999]))->assertNotFound();
    }

    public function test_unprepared_activity_and_legacy_submission_are_not_reconstructed(): void
    {
        $data = $this->activityData();
        unset($data['annex_j'], $data['itinerary']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data);
        $submission = $this->submit();
        $this->assertArrayNotHasKey('annex_j', $submission->snapshot);
        $this->get(route('itinerary.activity', $this->activity))->assertStatus(409);
        $this->get(route('itinerary.submission', ['publicId' => $submission->public_id]))->assertStatus(409);
    }

    public function test_blank_dot_information_is_optional_and_entered_text_is_escaped(): void
    {
        $data = $this->activityData();
        $data['itinerary'][0]['description'] = '<script>alert(1)</script>';
        $data['itinerary'][1]['dot_accreditation'] = null;
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->assertSame([], app(ItineraryBuilder::class)->missingFields(app(ItineraryBuilder::class)->snapshot($this->activity->latestVersion)));
        $this->get(route('itinerary.activity', $this->activity))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
    }

    public function test_long_itinerary_tables_paginate_with_all_rows_preserved(): void
    {
        $data = $this->activityData();
        $row = $data['itinerary'][0];
        $data['itinerary'] = [];
        for ($index = 0; $index < 35; $index++) {
            $data['itinerary'][] = array_merge($row, ['description' => 'Stop '.$index.' '.str_repeat('Fictional itinerary details. ', 12)]);
        }
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $response = $this->get(route('itinerary.activity', ['activity' => $this->activity->id, 'download' => 1]))->assertOk();
        $this->assertGreaterThan(1, preg_match_all('~/Type\s*/Page\b~', $response->getContent()));
        $this->assertCount(35, app(ItineraryBuilder::class)->snapshot($this->activity->latestVersion)['rows']);
        file_put_contents(storage_path('framework/testing/itinerary-long-layout.pdf'), $response->getContent());
    }

    public function test_scope_mfa_and_ched_jurisdiction_are_enforced(): void
    {
        $submission = $this->submit();
        $foreign = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $foreign->assignRole('hei_submitter');
        $this->actingAs($foreign)->get(route('itinerary.activity', $this->activity))->assertForbidden();
        $this->get(route('itinerary.submission', ['publicId' => $submission->public_id]))->assertForbidden();
        $reviewer = User::factory()->create(['region_id' => Region::create(['code' => 'III-D', 'name' => 'Other Region'])->id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer)->get(route('itinerary.activity', $this->activity))->assertForbidden();
        $reviewer->forceFill(['region_id' => $this->institution->region_id])->save();
        $this->actingAs($reviewer->fresh())->get(route('itinerary.activity', $this->activity))->assertOk();
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->actingAs($this->hei->fresh())->get(route('itinerary.activity', $this->activity))->assertForbidden();
        auth()->logout();
        $this->get(route('itinerary.activity', $this->activity))->assertRedirect();
    }

    public function test_ched_cannot_change_itinerary_even_with_activity_permission(): void
    {
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $reviewer->givePermissionTo('activities.manage');
        try {
            app(SaveActivity::class)->execute($reviewer, $this->activityData(), $this->activity);
            $this->fail('CHED changed an HEI itinerary.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame(1, $this->activity->versions()->count());
    }

    public function test_letterhead_is_private_and_quarantine_or_changed_bytes_block_output(): void
    {
        $image = UploadedFile::fake()->image('letterhead.png', 600, 80);
        $bytes = file_get_contents($image->getRealPath());
        Storage::disk('private')->put('itinerary-letterhead.png', $bytes);
        $document = Document::create(['institution_id' => $this->institution->id, 'document_type_id' => DocumentType::where('code', 'itinerary')->firstOrFail()->id,
            'public_id' => (string) Str::uuid(), 'path' => 'itinerary-letterhead.png', 'original_name' => 'letterhead.png', 'mime' => 'image/png',
            'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'confidentiality' => 'institutional', 'scan_status' => 'clean', 'is_signed' => false, 'uploaded_by' => $this->hei->id]);
        $data = $this->activityData();
        $data['annex_j']['letterhead_document_id'] = $document->id;
        $this->activity = app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $url = route('itinerary.activity', $this->activity);
        $this->get($url)->assertOk()->assertSee('data:image/png;base64,', false);
        $document->update(['scan_status' => 'pending']);
        $this->get($url)->assertStatus(409);
        $document->update(['scan_status' => 'clean']);
        Storage::disk('private')->put($document->path, 'changed bytes');
        $this->get($url)->assertStatus(409);
    }
}
