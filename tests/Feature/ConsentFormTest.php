<?php

namespace Tests\Feature;

use App\Actions\Activities\PrepareConsentForm;
use App\Actions\Activities\SaveActivity;
use App\Filament\Hei\Resources\Pages\ListActivity;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\AuditEvent;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ConsentFormTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Institution $institution;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
        Storage::fake('private');
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'ANNEX-L',
            'name' => 'Fictional Consent HEI', 'address' => 'Calamba, Laguna', 'institution_type' => 'private']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Representative', 'verified_at' => now(), 'is_authorized' => true]);
        $period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'AY 2026-2027',
            'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->activity = app(SaveActivity::class)->execute($this->hei, [
            'institution_id' => $this->institution->id, 'academic_period_id' => $period->id,
            'title' => 'Learning visit', 'category' => 2, 'academic_nature' => 'curricular', 'activity_type' => 'Field study',
            'initiator' => 'hei', 'description' => 'Learning visit', 'learning_objectives' => 'Observe practice',
            'responsible_office' => 'Student Affairs', 'starts_at' => '2026-12-10 09:00', 'ends_at' => '2026-12-10 17:00',
            'venue' => 'Laguna', 'student_count' => 20, 'conditions' => [],
            'authorization' => ['official' => 'President', 'date' => '2026-09-01'],
            'personnel' => [['role' => 'pic', 'name' => 'Faculty Person', 'position' => 'Faculty', 'contact' => 'pic@example.test']],
        ]);
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function fields(): array
    {
        return ['student_name' => 'Fictional Student', 'program_year_level' => 'BS Information Technology, Year 2',
            'student_number' => 'TEST-STUDENT-001', 'student_contact' => '09170000001', 'student_date' => '2026-10-07',
            'guardian_name' => 'Fictional Guardian', 'relationship' => 'Parent', 'emergency_contact' => '09170000002',
            'guardian_date' => '2026-10-07', 'student_privacy_date' => '2026-10-07', 'guardian_privacy_date' => '2026-10-07'];
    }

    private function url(): string
    {
        return route('consent.form', $this->activity->latestVersion);
    }

    private function image(Institution $institution): Document
    {
        $upload = UploadedFile::fake()->image('letterhead.png');
        $bytes = file_get_contents($upload->getRealPath());
        $path = 'fixtures/'.Str::uuid().'.png';
        Storage::disk('private')->put($path, $bytes);

        return Document::create(['institution_id' => $institution->id,
            'document_type_id' => DocumentType::where('code', 'certification')->firstOrFail()->id,
            'public_id' => (string) Str::uuid(), 'disk' => 'private', 'path' => $path,
            'original_name' => 'letterhead.png', 'mime' => 'image/png', 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes),
            'confidentiality' => 'institutional', 'scan_status' => 'clean', 'is_signed' => false, 'uploaded_by' => $this->hei->id]);
    }

    public function test_form_autofills_activity_and_exposes_optional_annex_l_fields(): void
    {
        $this->get($this->url())->assertOk()->assertSee('Learning visit')->assertSee('Student Affairs')
            ->assertSee('10 Dec 2026, 09:00')->assertSee('student_number')->assertSee('guardian_name')->assertSee('emergency_contact')
            ->assertSee('not record consent');
        Livewire::test(ListActivity::class)->assertActionVisible(TestAction::make('consent_form')->table($this->activity));
    }

    public function test_filled_preview_and_pdf_do_not_persist_personal_data_or_create_consent(): void
    {
        $before = $this->activity->latestVersion->getRawOriginal();
        $this->post($this->url(), $this->fields())->assertOk()->assertSee('Fictional Student')
            ->assertSee('Fictional Guardian')->assertSee('PARENT/GUARDIAN ACKNOWLEDGMENT')
            ->assertSee('Data Privacy Act of 2012')->assertSee('Download this printable PDF');
        $pdf = $this->post($this->url(), $this->fields() + ['output' => 'pdf'])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        file_put_contents(storage_path('framework/testing/consent-layout.pdf'), $pdf->getContent());
        $this->assertSame($before, $this->activity->latestVersion->fresh()->getRawOriginal());
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('submissions', 0);
        $this->assertDatabaseCount('activity_versions', 1);
        $metadata = AuditEvent::where('action', 'annex_l.generated')->firstOrFail()->metadata;
        $this->assertFalse($metadata['signed']);
        $this->assertFalse($metadata['personal_fields_persisted']);
        $this->assertStringNotContainsString('Fictional Student', json_encode($metadata));
        $this->assertStringNotContainsString('TEST-STUDENT-001', json_encode($metadata));
        $this->assertFalse(session()->has('_old_input'));
    }

    public function test_blank_form_is_printable_for_manual_completion(): void
    {
        $response = $this->post($this->url(), ['output' => 'pdf'])->assertOk();
        file_put_contents(storage_path('framework/testing/consent-blank-layout.pdf'), $response->getContent());
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_invalid_fields_return_errors_without_flashing_personal_input(): void
    {
        $this->post($this->url(), array_replace($this->fields(), ['student_name' => str_repeat('X', 151), 'guardian_date' => 'not-a-date']))
            ->assertStatus(422)->assertSee('Please correct:');
        $this->assertFalse(session()->has('_old_input'));
        $this->assertDatabaseCount('audit_events', 1);
    }

    public function test_student_input_is_escaped_and_cannot_override_institution_or_activity(): void
    {
        $this->post($this->url(), array_replace($this->fields(), ['student_name' => '<script>alert(1)</script>',
            'hei_name' => 'Spoof HEI', 'title' => 'Spoof activity', 'institution_id' => 999]))
            ->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false)
            ->assertSee('Fictional Consent HEI')->assertDontSee('Spoof HEI')->assertDontSee('Spoof activity');
    }

    public function test_missing_mfa_and_unverified_membership_are_blocked(): void
    {
        $this->hei->forceFill(['app_authentication_secret' => null])->save();
        $this->get($this->url())->assertForbidden();
        $this->post($this->url(), $this->fields())->assertForbidden();
        $this->hei->forceFill(['app_authentication_secret' => 'TESTSECRET'])->save();
        $this->hei->institutions()->updateExistingPivot($this->institution->id, ['verified_at' => null]);
        $this->get($this->url())->assertForbidden();
    }

    public function test_other_institution_and_ched_accounts_cannot_prepare_personal_consent(): void
    {
        $other = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $other->assignRole('hei_submitter');
        $this->actingAs($other)->get($this->url())->assertForbidden();
        $staff = User::factory()->create(['app_authentication_secret' => 'TESTSECRET', 'region_id' => $this->institution->region_id]);
        $staff->assignRole('ched_signatory');
        $this->actingAs($staff)->post($this->url(), $this->fields())->assertForbidden();
    }

    public function test_old_version_url_keeps_original_planned_facts(): void
    {
        $url = $this->url();
        $version = $this->activity->latestVersion;
        $data = $version->toArray();
        $data['institution_id'] = $this->institution->id;
        $data['academic_period_id'] = $this->activity->academic_period_id;
        $data['title'] = 'Changed plan title';
        $data['personnel'] = $version->personnel->toArray();
        app(SaveActivity::class)->execute($this->hei, $data, $this->activity);
        $this->get($url)->assertOk()->assertSee('Learning visit')->assertDontSee('Changed plan title');
    }

    public function test_private_letterhead_scan_and_hash_are_rechecked(): void
    {
        $image = $this->image($this->institution);
        $fields = $this->fields() + ['letterhead_document_id' => $image->id];
        $this->post($this->url(), $fields)->assertOk()->assertSee('data:image/png', false);
        $image->update(['scan_status' => 'quarantined']);
        $this->post($this->url(), $fields)->assertStatus(409);
        $image->update(['scan_status' => 'clean']);
        Storage::disk('private')->put($image->path, 'tampered');
        $this->post($this->url(), $fields)->assertStatus(409);
    }

    public function test_other_institution_letterhead_is_hidden_and_blocked(): void
    {
        $other = Institution::create(['region_id' => $this->institution->region_id, 'ched_code' => 'OTHER-L',
            'name' => 'Other HEI', 'institution_type' => 'private']);
        $image = $this->image($other);
        $this->get($this->url())->assertDontSee('letterhead.png');
        $this->post($this->url(), $this->fields() + ['letterhead_document_id' => $image->id])->assertForbidden();
    }

    public function test_personal_query_parameters_are_not_read_or_rendered(): void
    {
        $this->get($this->url().'?student_name=UnwantedQueryName')->assertOk()->assertDontSee('UnwantedQueryName');
        $data = app(PrepareConsentForm::class)->execute($this->hei, $this->activity->latestVersion, $this->fields());
        $this->assertSame('L', $data['template']['annex']);
        $this->assertSame(32, $data['template']['source_page']);
    }
}
