<?php

namespace Tests\Feature;

use App\Actions\Operations\RecordCaseEvent;
use App\Actions\Operations\RecordOperationalRecord;
use App\Filament\Ched\Resources\Pages\ViewCaseRecord;
use App\Models\AuditEvent;
use App\Models\CaseRecord;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use App\Services\CaseProcedure;
use Carbon\CarbonImmutable;
use Database\Seeders\CaseEvidenceTypeSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CaseProcedureTest extends TestCase
{
    use RefreshDatabase;

    private User $officer;

    private Institution $institution;

    private CaseRecord $case;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 12:00', 'Asia/Manila'));
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'CASE-QA', 'name' => 'Fictional case HEI', 'institution_type' => 'private']);
        $this->officer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $this->officer->assignRole('ched_case_officer');
        $this->case = app(RecordOperationalRecord::class)->execute($this->officer, 'case', ['institution_id' => $this->institution->id, 'reference' => 'QA-COMPLAINT',
            'type' => 'complaint', 'status' => 'received', 'summary' => 'Fictional written complaint', 'opened_at' => '2026-10-01', 'source_document_id' => $this->document('complaint')->id]);
    }

    private function document(string $code = 'case_evidence', array $attributes = []): Document
    {
        $type = DocumentType::where('code', $code)->firstOrFail();

        return Document::create(array_replace(['institution_id' => $this->institution->id, 'document_type_id' => $type->id, 'public_id' => (string) Str::uuid(),
            'path' => 'qa/'.Str::uuid().'.pdf', 'original_name' => 'fictional-'.$code.'.pdf', 'mime' => 'application/pdf', 'size' => 9,
            'sha256' => hash('sha256', '%PDF-test'), 'confidentiality' => 'restricted', 'scan_status' => 'clean', 'is_signed' => true, 'uploaded_by' => $this->officer->id], $attributes));
    }

    private function record(string $stage, array $details = [], ?Document $document = null): int
    {
        app(RecordCaseEvent::class)->execute($this->officer, $this->case, $stage, 'Fictional '.$stage, ($document ?? $this->document(in_array($stage, ['decided', 'closed', 'investigating'], true) ? 'official_order' : 'case_evidence'))->id, $details);

        return $this->case->events()->latest('id')->value('id');
    }

    public function test_complete_case_chain_dispatch_and_receipt_are_evidenced_without_creating_restriction(): void
    {
        $this->record('referred', ['destination_region_id' => $this->institution->region_id, 'jurisdiction_basis' => 'hei', 'reference' => 'REF-1']);
        $this->record('fact_finding');
        $this->record('investigation_findings');
        $this->record('committee_recommendation', ['reference' => 'COMMITTEE-1']);
        $decision = $this->record('decided', ['authority' => 'Commission en Banc (CEB)', 'reference' => 'CEB-1']);
        $dispatch = $this->record('hei_dispatched', ['decision_event_id' => $decision, 'recipient' => 'HEI President office', 'channel' => 'post', 'reference' => 'POST-1']);
        $this->record('hei_received', ['dispatch_event_id' => $dispatch, 'recipient' => 'HEI Records', 'reference' => 'RECEIPT-1']);
        $this->assertSame([], app(CaseProcedure::class)->missing($this->case));
        $this->assertSame('decided', $this->case->fresh()->status);
        $this->assertDatabaseCount('institutional_restrictions', 0);
        $this->assertSame('complaint', $this->case->type);
        $this->assertDatabaseCount('case_events', 8);
    }

    public function test_historical_external_decision_preserves_unknown_prior_history_without_fabricating_events(): void
    {
        $this->record('decided', ['authority' => 'Commission en Banc (CEB)', 'reference' => 'OLD-CEB', 'historical_external' => true, 'history_explanation' => 'External signed decision supplied; earlier regional records unavailable.']);
        $event = $this->case->events()->latest('id')->firstOrFail();
        $this->assertSame(['referred', 'fact_finding', 'investigation_findings', 'committee_recommendation'], $event->procedure['missing_prior_stages']);
        $this->assertDatabaseCount('case_events', 2);
        $this->assertStringContainsString('Earlier stages unavailable at recording', app(CaseProcedure::class)->history($this->case));
    }

    public function test_new_decision_requires_prior_history_and_failure_rolls_back(): void
    {
        try {
            $this->record('decided', ['authority' => 'Commission en Banc (CEB)', 'reference' => 'CEB-1']);
            $this->fail('Incomplete new history must not be treated as complete.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('details.history_explanation', $exception->errors());
        }
        $this->assertSame('received', $this->case->fresh()->status);
        $this->assertDatabaseCount('case_events', 1);
    }

    public function test_location_referral_records_destination_without_changing_access_or_investigation_status(): void
    {
        $region = Region::create(['code' => 'QA-V', 'name' => 'Fictional destination']);
        $this->record('referred', ['destination_region_id' => $region->id, 'jurisdiction_basis' => 'activity_location', 'activity_location' => 'Fictional location', 'reference' => 'LOC-1']);
        $this->assertSame($this->institution->region_id, $this->case->fresh()->jurisdiction_region_id);
        $this->assertDatabaseCount('institutional_restrictions', 0);
        $this->assertSame($region->id, $this->case->events()->latest('id')->first()->procedure['destination_region_id']);
    }

    public function test_replayed_event_key_is_rejected_without_duplicate_history_or_audit(): void
    {
        $key = (string) Str::uuid();
        $this->record('fact_finding', ['event_key' => $key]);
        $count = AuditEvent::count();
        try {
            $this->record('fact_finding', ['event_key' => $key]);
            $this->fail('Duplicate key should fail.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('case_events', 2);
        $this->assertSame($count, AuditEvent::count());
    }

    public function test_quarantined_or_nonrestricted_case_evidence_cannot_be_attached(): void
    {
        foreach ([['scan_status' => 'pending'], ['confidentiality' => 'institutional'], ['institution_id' => Institution::create(['region_id' => $this->institution->region_id, 'ched_code' => 'OTHER-QA', 'name' => 'Other', 'institution_type' => 'private'])->id]] as $attributes) {
            try {
                $this->record('fact_finding', document: $this->document(attributes: $attributes));
                $this->fail('Invalid attachment should fail.');
            } catch (HttpException $exception) {
                $this->assertSame(422, $exception->getStatusCode());
            }
        }
        $this->assertDatabaseCount('case_events', 1);
    }

    public function test_visible_form_and_history_use_ched_committee_and_record_referral_fields(): void
    {
        $this->actingAs($this->officer);
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        Livewire::test(ViewCaseRecord::class, ['record' => $this->case->id])->assertSee('Restricted procedural history')->assertSee('Not recorded:')
            ->callAction('case_status', data: ['status' => 'referred', 'notes' => 'Fictional referral', 'document_id' => $this->document()->id,
                'details' => ['occurred_at' => '2026-10-12 11:00', 'reference' => 'REF-UI', 'destination_region_id' => $this->institution->region_id, 'jurisdiction_basis' => 'hei']])->assertHasNoActionErrors();
        $this->assertDatabaseHas('case_events', ['case_record_id' => $this->case->id, 'type' => 'referred']);
        Livewire::test(ViewCaseRecord::class, ['record' => $this->case->id])->assertSee('Referral destination:')->assertSee('CHED Committee recommendation');
    }

    public function test_case_history_is_denied_to_admin_hei_other_region_and_unverified_staff(): void
    {
        foreach (['system_administrator', 'hei_submitter', 'other_region', 'unverified'] as $kind) {
            $user = User::factory()->create(['region_id' => $kind === 'other_region' ? Region::create(['code' => 'CASE-OTHER', 'name' => 'Other region'])->id : $this->institution->region_id,
                'email_verified_at' => $kind === 'unverified' ? null : now()]);
            $user->assignRole(in_array($kind, ['other_region', 'unverified'], true) ? 'ched_case_officer' : $kind);
            $this->assertFalse($user->can('view', $this->case));
            try {
                app(RecordCaseEvent::class)->execute($user, $this->case, 'fact_finding', 'Unauthorized', $this->document()->id);
                $this->fail('Unauthorized case event must fail.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_legacy_events_remain_unchanged_and_are_explicitly_marked_legacy(): void
    {
        $legacy = $this->case->events()->create(['type' => 'fact_finding', 'occurred_at' => now(), 'notes' => 'Historical entry', 'created_by' => $this->officer->id]);
        $before = $legacy->fresh()->getRawOriginal();
        $this->record('investigation_findings');
        $this->assertSame($before, $legacy->fresh()->getRawOriginal());
        $this->assertStringContainsString('Legacy event: structured particulars were not recorded.', app(CaseProcedure::class)->history($this->case));
        $this->expectException(\LogicException::class);
        $legacy->update(['notes' => 'Overwrite']);
    }

    public function test_receipt_requires_a_dispatch_link_and_cannot_predate_it(): void
    {
        $this->record('decided', ['authority' => 'Commission en Banc (CEB)', 'reference' => 'CEB-OLD', 'historical_external' => true, 'history_explanation' => 'Prior evidence unavailable.']);
        $decision = $this->case->events()->latest('id')->value('id');
        $dispatch = $this->record('hei_dispatched', ['decision_event_id' => $decision, 'recipient' => 'HEI', 'channel' => 'post', 'reference' => 'MAIL-1']);
        foreach ([['dispatch_event_id' => $decision], ['dispatch_event_id' => $dispatch, 'occurred_at' => '2026-10-11 12:00']] as $details) {
            try {
                $this->record('hei_received', $details + ['recipient' => 'HEI', 'reference' => 'ACK-1']);
                $this->fail('Receipt must identify an earlier dispatch.');
            } catch (HttpException $exception) {
                $this->assertSame(422, $exception->getStatusCode());
            }
        }
        $this->assertDatabaseCount('case_events', 3);
    }

    public function test_historical_mode_requires_explanation_and_preserves_actual_external_date(): void
    {
        try {
            $this->record('decided', ['authority' => 'Commission en Banc (CEB)', 'reference' => 'OLD', 'historical_external' => true]);
            $this->fail('Historical mode needs an explanation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('details.history_explanation', $exception->errors());
        }
        $this->record('decided', ['authority' => 'Commission en Banc (CEB)', 'reference' => 'OLD', 'historical_external' => true,
            'occurred_at' => '2026-09-01 09:00', 'history_explanation' => 'Decision predates registry intake; prior steps unavailable.']);
        $this->assertSame('2026-09-01 01:00', $this->case->events()->latest('id')->first()->occurred_at->format('Y-m-d H:i'));
    }

    public function test_wrong_decision_authority_unsigned_order_and_reopening_are_rejected(): void
    {
        foreach ([['authority' => 'CHEDRO'], ['authority' => 'Commission en Banc (CEB)', 'unsigned' => true]] as $case) {
            try {
                $this->record('decided', ['authority' => $case['authority'], 'reference' => 'ORDER', 'historical_external' => true, 'history_explanation' => 'Earlier records absent.'],
                    $this->document('official_order', ['is_signed' => ! ($case['unsigned'] ?? false)]));
                $this->fail('Decision requires signed order and CEB authority.');
            } catch (HttpException $exception) {
                $this->assertSame(422, $exception->getStatusCode());
            }
        }
        $this->record('decided', ['authority' => 'Commission en Banc (CEB)', 'reference' => 'ORDER', 'historical_external' => true, 'history_explanation' => 'Earlier records absent.']);
        $this->expectException(HttpException::class);
        $this->record('received');
    }

    public function test_case_migration_and_document_catalog_are_repeatable_without_backfill(): void
    {
        $legacy = $this->case->events()->create(['type' => 'fact_finding', 'occurred_at' => now(), 'notes' => 'Legacy', 'created_by' => $this->officer->id]);
        $before = $legacy->fresh()->getRawOriginal();
        $migration = require database_path('migrations/2026_10_12_023309_add_procedure_to_case_events.php');
        $migration->up();
        $migration->up();
        $this->seed(CaseEvidenceTypeSeeder::class);
        $this->seed(CaseEvidenceTypeSeeder::class);
        $this->assertSame($before, $legacy->fresh()->getRawOriginal());
        $this->assertSame(1, DocumentType::where('code', 'case_evidence')->count());
        $this->expectException(\LogicException::class);
        $migration->down();
    }

    public function test_dispatch_cannot_link_a_decision_from_another_case(): void
    {
        $other = $this->case->replicate();
        $other->reference = 'QA-OTHER';
        $other->save();
        $event = $other->events()->create(['type' => 'decided', 'occurred_at' => now(), 'notes' => 'Other case decision', 'document_id' => $this->document('official_order')->id, 'created_by' => $this->officer->id]);
        $this->expectException(HttpException::class);
        $this->record('hei_dispatched', ['decision_event_id' => $event->id, 'recipient' => 'HEI', 'reference' => 'DISPATCH', 'channel' => 'post']);
    }

    public function test_hei_jurisdiction_referral_cannot_claim_another_institution_region(): void
    {
        $other = Region::create(['code' => 'QA-REF', 'name' => 'Other']);
        $this->expectException(HttpException::class);
        $this->record('referred', ['destination_region_id' => $other->id, 'jurisdiction_basis' => 'hei', 'reference' => 'WRONG']);
    }
}
