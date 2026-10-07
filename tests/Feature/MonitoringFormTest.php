<?php

namespace Tests\Feature;

use App\Actions\Operations\RecordOperationalRecord;
use App\Filament\Ched\Resources\Pages\ListMonitoringRecord;
use App\Filament\Ched\Resources\Pages\CreateMonitoringRecord;
use App\Filament\Ched\Resources\MonitoringRecordResource;
use App\Models\Institution;
use App\Models\MonitoringRecord;
use App\Models\Region;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MonitoringFormTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'ANNEX-M',
            'name' => 'Fictional Monitoring HEI', 'address' => 'Calamba, Laguna', 'institution_type' => 'suc']);
        $this->staff = User::factory()->create(['name' => 'Fictional Monitoring Officer', 'region_id' => $this->institution->region_id,
            'app_authentication_secret' => 'TESTSECRET']);
        $this->staff->assignRole('ched_monitor');
        $this->actingAs($this->staff);
        Filament::setCurrentPanel(Filament::getPanel('ched'));
    }

    private function data(): array
    {
        return ['institution_id' => $this->institution->id, 'period' => 'AY 2026-2027, first semester',
            'monitored_on' => '2026-10-07', 'type' => 'on_site', 'respondent' => 'Fictional HEI Respondent',
            'overall_rating' => 'compliant', 'findings' => 'No deficiencies noted.', 'good_practices' => 'Organized records.',
            'suggestions' => 'Continue improving documentation.',
            'annex_metadata' => ['respondent_position' => 'Student Affairs Director',
                'prepared_by' => 'Fictional Preparer', 'prepared_position' => 'Education Supervisor',
                'reviewed_by' => 'Fictional Reviewer', 'reviewed_position' => 'Chief',
                'noted_by' => 'Fictional Director', 'noted_position' => 'Director IV',
                'hei_conforme' => 'Fictional HEI Representative', 'hei_conforme_date' => '2026-10-07', 'other_findings' => 'None.'],
            'items' => collect(config('monitoring.annex_m'))->map(fn ($row, $code) => ['criterion_code' => (string) $code, 'criterion' => $row['criterion'],
                'compliance' => 'yes', 'verification' => $row['verification'], 'remarks' => 'Verified.'])->values()->all()];
    }

    private function record(?array $data = null): MonitoringRecord
    {
        return app(RecordOperationalRecord::class)->execute($this->staff, 'monitoring', $data ?? $this->data());
    }

    public function test_complete_annex_m_snapshots_and_renders_all_template_fields(): void
    {
        $record = $this->record();
        $this->assertCount(14, $record->annex_m['items']);
        $this->assertSame('SUC', $record->annex_m['hei_type']);
        $this->assertSame('Fictional Monitoring Officer', $record->annex_m['monitored_by']);
        $this->assertSame([33, 34], $record->annex_m['template']['source_pages']);
        $this->assertSame('Student Affairs Director', $record->annex_m['respondent_position']);
        $this->assertSame('2026-10-07', $record->annex_m['hei_conforme']['date']);
        $this->get(route('monitoring.print', $record))->assertOk()->assertSee('Established and implemented')
            ->assertSee('HEI Conforme')->assertSee('Fictional Director')->assertSee('14. Submitted the Report');
        $pdf = $this->get(route('monitoring.print', ['record' => $record, 'download' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        file_put_contents(storage_path('framework/testing/monitoring-layout.pdf'), $pdf->getContent());
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseHas('audit_events', ['action' => 'annex_m.generated']);
    }

    public function test_form_saves_all_sections_through_shared_action(): void
    {
        Livewire::test(CreateMonitoringRecord::class)->fillForm($this->data())->call('create')->assertHasNoFormErrors();
        $record = MonitoringRecord::firstOrFail();
        $this->assertSame('Education Supervisor', $record->annex_m['signatories']['Prepared by']['position']);
        $this->assertSame(14, $record->items()->count());
        Livewire::test(ListMonitoringRecord::class)->assertActionVisible(TestAction::make('monitoring_print')->table($record));
    }

    public function test_new_monitoring_page_renders_annex_m_fields_and_default_criteria(): void
    {
        Livewire::test(ListMonitoringRecord::class)->assertActionHasUrl('create', MonitoringRecordResource::getUrl('create'));
        $component = Livewire::test(CreateMonitoringRecord::class);
        $html = $component->html();
        foreach (['Period covered', 'Date of monitoring', 'Annex M compliance matrix (14 items)',
            'Means of verification', 'Deficiencies noted', 'Printed signatories and HEI conforme'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertCount(14, $component->get('data.items'));
    }

    public function test_later_profile_staff_and_configuration_changes_do_not_change_preserved_output(): void
    {
        $record = $this->record();
        $frozen = $record->annex_m;
        $this->institution->update(['name' => 'Later HEI', 'institution_type' => 'private']);
        $this->staff->update(['name' => 'Later Officer']);
        config(['monitoring.annex_m.1.criterion' => 'Later criterion']);
        $this->get(route('monitoring.print', $record))->assertOk()->assertSee('Fictional Monitoring HEI')
            ->assertSee('Fictional Monitoring Officer')->assertDontSee('Later criterion')->assertDontSee('Later HEI');
        $this->assertSame($frozen, $record->fresh()->annex_m);
    }

    public function test_payload_is_immutable(): void
    {
        $record = $this->record();
        $this->expectException(\LogicException::class);
        $record->update(['annex_m' => []]);
    }

    public function test_duplicate_missing_or_unknown_criteria_are_rejected_atomically(): void
    {
        $data = $this->data();
        $data['items'][13]['criterion_code'] = '1';
        try {
            $this->record($data);
            $this->fail('Duplicate criterion accepted.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
        $this->assertDatabaseCount('monitoring_records', 0);
        $data['items'][13]['criterion_code'] = '15';
        $this->expectException(ValidationException::class);
        $this->record($data);
    }

    public function test_spoofed_criteria_and_region_cannot_replace_source_definitions(): void
    {
        $data = $this->data();
        $data['items'][0]['criterion'] = 'User-invented criterion';
        $record = $this->record($data);
        $this->assertNotSame('User-invented criterion', $record->items()->first()->criterion);
        $data['annex_metadata']['region_id'] = '999';
        $this->expectException(ValidationException::class);
        $this->record($data);
    }

    public function test_hei_other_region_and_missing_mfa_access_are_blocked(): void
    {
        $record = $this->record();
        $hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $hei->assignRole('hei_submitter');
        $hei->institutions()->attach($this->institution->id, ['position' => 'Representative', 'verified_at' => now(), 'is_authorized' => true]);
        $this->actingAs($hei)->get(route('monitoring.print', $record))->assertForbidden();
        $other = Region::create(['code' => 'OTHER-M', 'name' => 'Other region']);
        $this->staff->forceFill(['region_id' => $other->id])->save();
        $this->actingAs($this->staff)->get(route('monitoring.print', $record))->assertForbidden();
        $this->staff->forceFill(['region_id' => $this->institution->region_id, 'app_authentication_secret' => null])->save();
        $this->get(route('monitoring.print', $record))->assertForbidden();
    }

    public function test_hei_cannot_create_monitoring_even_for_its_institution(): void
    {
        $hei = User::factory()->create();
        $hei->assignRole('hei_submitter');
        $this->expectException(HttpException::class);
        app(RecordOperationalRecord::class)->execute($hei, 'monitoring', $this->data());
    }

    public function test_legacy_record_does_not_get_reconstructed_printable_data(): void
    {
        $data = $this->data();
        unset($data['items'], $data['annex_metadata']);
        $record = MonitoringRecord::create($data + ['monitored_by' => $this->staff->id]);
        $this->get(route('monitoring.print', $record))->assertStatus(409);
        $this->assertNull($record->fresh()->annex_m);
    }

    public function test_optional_signatures_are_blank_and_not_inferred_from_account(): void
    {
        $data = $this->data();
        unset($data['annex_metadata']);
        $record = $this->record($data);
        $this->assertNull($record->annex_m['signatories']['Prepared by']['name']);
        $this->assertNull($record->annex_m['hei_conforme']['date']);
        $this->get(route('monitoring.print', ['record' => $record, 'download' => 1]))->assertOk();
    }

    public function test_long_findings_and_verification_generate_complete_paginated_output(): void
    {
        $data = $this->data();
        $data['findings'] = str_repeat('Detailed institutional findings. ', 55).'END-FINDINGS';
        $data['items'][0]['verification'] = str_repeat('Verified evidence references. ', 55).'END-VERIFICATION';
        $data['items'][0]['remarks'] = str_repeat('Institutional remark. ', 70).'END-REMARKS';
        $record = $this->record($data);
        $pdf = $this->get(route('monitoring.print', ['record' => $record, 'download' => 1]))->assertOk();
        file_put_contents(storage_path('framework/testing/monitoring-long-layout.pdf'), $pdf->getContent());
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_html_narratives_are_escaped_and_output_is_private(): void
    {
        $record = $this->record(array_replace($this->data(), ['findings' => '<script>alert(1)</script>']));
        $this->get(route('monitoring.print', $record))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false)
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
