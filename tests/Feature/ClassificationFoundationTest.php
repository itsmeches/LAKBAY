<?php

namespace Tests\Feature;

use App\Actions\Submissions\ProposeClassificationRuleSet;
use App\Models\ActivityVersion;
use App\Models\AuditEvent;
use App\Models\ClassificationAssessment;
use App\Models\ClassificationRuleSet;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\ClassificationRuleSetSeeder;
use Database\Seeders\QaDemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ClassificationFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Storage::fake('private');
        Storage::fake('local');
        $this->seed(QaDemoSeeder::class);
    }

    public function test_migration_and_seed_are_repeatable_without_changing_history(): void
    {
        $tables = ['activity_versions', 'activity_classifications', 'submissions', 'submission_requirements', 'certification_drafts', 'acknowledgments', 'audit_events', 'policy_versions'];
        $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
        $migration = require database_path('migrations/2026_10_08_072509_create_classification_rule_sets_and_assessments.php');
        $migration->up();
        $this->seed(ClassificationRuleSetSeeder::class);
        $first = ClassificationRuleSet::firstOrFail()->getAttributes();
        $migration->up();
        $this->seed(ClassificationRuleSetSeeder::class);
        $this->assertSame(1, ClassificationRuleSet::count());
        $this->assertSame($first, ClassificationRuleSet::firstOrFail()->getAttributes());
        foreach ($before as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson(), $table);
        }
        $this->assertSame(0, ClassificationAssessment::count());
    }

    public function test_every_materiality_default_has_provenance_and_no_numeric_exemption(): void
    {
        $this->seed(ClassificationRuleSetSeeder::class);
        $set = ClassificationRuleSet::firstOrFail();
        $this->assertSame('proposed', $set->status);
        $this->assertSame(22, count($set->rules['materiality']));
        foreach ($set->rules['materiality'] as $row) {
            $this->assertStringContainsString('CMO No. 24', $row['source_reference']);
            $this->assertStringContainsString('PDF', $row['source_reference']);
            $this->assertStringContainsString('Project owner', $row['decision_source']);
            $this->assertArrayNotHasKey('threshold', $row);
        }
        $this->assertSame('unresolved', $set->rules['timing']['rcd_anchor']);
        $this->assertEqualsCanonicalizing(['strenuous', 'requires_accommodation', 'long_distance', 'vulnerable_participants', 'class_size'], $set->rules['category_3_escalation']['triggers']);
        $this->assertSame('candidate', $set->policyVersion->status);
    }

    public function test_rule_changes_append_a_successor_without_replacing_used_configuration(): void
    {
        $set = ClassificationRuleSet::factory()->create(['rules' => ['venue' => 'rcd_only']]);
        $successor = ClassificationRuleSet::factory()->create(['supersedes_id' => $set->id, 'rules' => ['venue' => 'category_2_and_rcd']]);
        $this->assertSame($set->id, $successor->supersedes->id);
        $this->assertSame(['venue' => 'rcd_only'], $set->fresh()->rules);
        $this->expectException(\LogicException::class);
        $set->update(['rules' => []]);
    }

    public function test_assessment_chain_preserves_baseline_actor_and_requirement_snapshots(): void
    {
        $version = ActivityVersion::firstOrFail();
        $first = ClassificationAssessment::factory()->create(['baseline_version_id' => $version->id,
            'requirements_before' => ['category' => 1, 'codes' => ['saa']], 'requirements_after' => ['category' => 2, 'codes' => ['saa', 'invitation']]]);
        $next = ClassificationAssessment::factory()->create(['previous_assessment_id' => $first->id,
            'baseline_version_id' => $version->id, 'state' => 'confirmed', 'confirmed_category' => 2,
            'confirmed_by' => $first->recorded_by, 'confirmed_at' => now()]);
        $this->assertSame($first->id, $next->previousAssessment->id);
        $this->assertSame($version->id, $next->baselineVersion->id);
        $this->assertSame($next->confirmed_by, $next->confirmer->id);
        $this->assertSame(['saa', 'invitation'], $first->fresh()->requirements_after['codes']);
        $this->assertSame('unresolved', $next->timing['rcd_anchor']);
        $this->expectException(\LogicException::class);
        $first->update(['reason' => 'overwrite']);
    }

    public function test_assessment_cannot_be_deleted(): void
    {
        $assessment = ClassificationAssessment::factory()->create();
        $this->expectException(\LogicException::class);
        $assessment->delete();
    }

    public function test_duplicate_event_keys_are_rejected(): void
    {
        ClassificationAssessment::factory()->create(['event_key' => 'same-event']);
        $this->expectException(QueryException::class);
        ClassificationAssessment::factory()->create(['event_key' => 'same-event']);
    }

    public function test_rule_set_cannot_be_deleted(): void
    {
        $set = ClassificationRuleSet::factory()->create();
        $this->expectException(\LogicException::class);
        $set->delete();
    }

    public function test_authorized_configuration_proposal_is_audited_idempotent_and_does_not_activate_policy(): void
    {
        $this->seed(ClassificationRuleSetSeeder::class);
        $source = ClassificationRuleSet::firstOrFail();
        $actor = User::factory()->create(['region_id' => Region::firstOrFail()->id]);
        $actor->givePermissionTo(['panel.ched', 'policy.manage']);
        $rules = $source->rules;
        $rules['materiality']['venue']['default_outcome'] = 'rcd_only';
        $rules['materiality']['venue']['decision_source'] = 'Fictional CHED clarification, test only';
        $data = ['code' => 'TEST-CONFIG-SUCCESSOR', 'rules' => $rules, 'decision_source' => 'Fictional test clarification'];
        $before = $source->policyVersion->getAttributes();
        $proposal = app(ProposeClassificationRuleSet::class)->execute($actor, $source, $data);
        $auditCount = AuditEvent::count();
        $again = app(ProposeClassificationRuleSet::class)->execute($actor, $source, $data);
        $this->assertSame($proposal->id, $again->id);
        $this->assertSame($auditCount, AuditEvent::count());
        $this->assertSame('proposed', $proposal->status);
        $this->assertSame('category_2_and_rcd', $source->fresh()->rules['materiality']['venue']['default_outcome']);
        $this->assertSame($before, $source->policyVersion->fresh()->getAttributes());
        $this->assertDatabaseHas('audit_events', ['actor_id' => $actor->id, 'action' => 'classification.rules.proposed', 'record_id' => $proposal->id]);
        $data['rules']['materiality']['venue']['default_outcome'] = 'record_only';
        $this->expectException(ValidationException::class);
        app(ProposeClassificationRuleSet::class)->execute($actor, $source, $data);
    }

    public function test_hei_cannot_propose_classification_configuration(): void
    {
        $this->seed(ClassificationRuleSetSeeder::class);
        $actor = User::factory()->create();
        $actor->assignRole('hei_preparer');
        $this->expectException(HttpException::class);
        app(ProposeClassificationRuleSet::class)->execute($actor, ClassificationRuleSet::firstOrFail(), []);
    }

    public function test_schema_rollback_refuses_to_destroy_classification_history(): void
    {
        $migration = require database_path('migrations/2026_10_08_072509_create_classification_rule_sets_and_assessments.php');
        $this->expectException(\LogicException::class);
        $migration->down();
    }
}
