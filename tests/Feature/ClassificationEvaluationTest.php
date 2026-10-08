<?php

namespace Tests\Feature;

use App\Actions\Activities\SaveActivity;
use App\Actions\Submissions\SubmitActivity;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\ClassificationAssessment;
use App\Models\ClassificationRuleSet;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Holiday;
use App\Models\Institution;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\Submission;
use App\Models\SubmissionRequirement;
use App\Models\User;
use App\Services\Compliance\RequirementResolver;
use Carbon\CarbonImmutable;
use Database\Seeders\ClassificationEvaluationRuleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ClassificationEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Calendar $calendar;

    private ClassificationRuleSet $rules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-01 09:00', 'Asia/Manila'));
        config(['lakbay.allow_draft_policy' => true]);
        $this->seed();
        $this->seed(ClassificationEvaluationRuleSeeder::class);
        Queue::fake();
        $this->rules = ClassificationRuleSet::where('code', 'CMO24-GAP2-EVALUATION-V2')->firstOrFail();
        $institution = Institution::create(['region_id' => Region::firstOrFail()->id, 'ched_code' => 'SLICE2', 'name' => 'Fictional Slice 2 HEI', 'institution_type' => 'suc']);
        $this->hei = User::factory()->create();
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($institution->id, ['position' => 'Fictional authorized submitter', 'is_authorized' => true, 'verified_at' => now()]);
        $period = AcademicPeriod::create(['institution_id' => $institution->id, 'label' => 'AY test', 'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->calendar = Calendar::create(['institution_id' => $institution->id, 'academic_period_id' => $period->id, 'title' => 'Fictional CLOCA', 'responsible_office' => 'OSAS', 'created_by' => $this->hei->id]);
    }

    private function data(int $category = 1): array
    {
        return ['institution_id' => $this->calendar->institution_id, 'academic_period_id' => $this->calendar->academic_period_id,
            'calendar_id' => $category === 1 ? $this->calendar->id : null, 'title' => 'Fictional learning visit', 'category' => $category,
            'academic_nature' => 'curricular', 'activity_type' => 'Learning visit', 'initiator' => $category === 3 ? 'student' : 'hei',
            'description' => 'Observe local systems', 'learning_objectives' => 'Apply classroom learning', 'responsible_office' => 'OSAS',
            'starts_at' => '2026-11-16 09:00', 'ends_at' => '2026-11-16 17:00', 'venue' => 'Fictional museum', 'student_count' => 20,
            'conditions' => ['requires_physical_activity' => false, 'requires_travel' => false, 'strenuous' => false,
                'requires_accommodation' => false, 'long_distance' => false, 'vulnerable_participants' => false, 'class_size' => false], 'authorization' => ['official' => 'Fictional HEI Head', 'date' => '2026-09-30'],
            'personnel' => [['role' => 'pic', 'name' => 'Fictional PIC', 'position' => 'Faculty', 'contact' => 'pic@example.test',
                'hei_employed' => true, 'accompanies_activity' => true, 'first_aid_trained' => true,
                'qualifications' => 'Fictional relevant experience', 'first_aid_reference' => 'Fictional training reference']],
            'classification' => ['rule_set_id' => $this->rules->id, 'planned_on' => '2026-09-15', 'regular_process_confirmed' => true,
                'student_supervision_reference' => 'Fictional faculty approval', 'confirm' => true, 'reason' => 'HEI confirms recorded facts.']];
    }

    private function create(int $category = 1, array $changes = []): Activity
    {
        return app(SaveActivity::class)->execute($this->hei, array_replace_recursive($this->data($category), $changes));
    }

    private function revise(Activity $activity, array $changes = []): Activity
    {
        $v = $activity->fresh()->latestVersion;
        $data = $v->only(['category', 'title', 'academic_nature', 'activity_type', 'initiator', 'description', 'learning_objectives', 'responsible_office', 'starts_at', 'ends_at', 'venue', 'student_count', 'conditions', 'authorization']);
        $data += $activity->only(['institution_id', 'academic_period_id', 'calendar_id', 'campus_id']);
        foreach (['starts_at', 'ends_at'] as $date) {
            $data[$date] = $v->$date->timezone('Asia/Manila')->format('Y-m-d H:i:s');
        }
        $data['personnel'] = $v->personnel->map(fn ($person) => $person->only(['role', 'name', 'position', 'contact', 'hei_employed', 'accompanies_activity', 'first_aid_trained', 'qualifications', 'first_aid_reference']))->all();
        $data['classification'] = ['expected_version_id' => $v->id, 'confirm' => true, 'reason' => 'HEI confirms this recorded change.'];

        return app(SaveActivity::class)->execute($this->hei, array_replace_recursive($data, $changes), $activity);
    }

    private function assessment(Activity $activity): ClassificationAssessment
    {
        return ClassificationAssessment::where('activity_version_id', $activity->fresh()->latestVersion->id)->firstOrFail();
    }

    private function receipt(Activity $activity, ?int $policyId = null, string $stage = 'annual_calendar'): Submission
    {
        $version = $activity->latestVersion;
        $submission = Submission::create(['institution_id' => $activity->institution_id, 'calendar_id' => $activity->calendar_id,
            'policy_version_id' => $policyId ?? $this->rules->policy_version_id, 'public_id' => (string) Str::uuid(), 'reference' => 'TEST-'.Str::uuid(),
            'type' => 'calendar', 'stage' => $stage, 'status' => 'acknowledged', 'submitted_by' => $this->hei->id,
            'submitted_at' => now(), 'received_at' => now(), 'holiday_calendar_version' => 'test', 'snapshot' => ['original' => $version->toArray()]]);
        $submission->activityVersions()->attach($version->id);
        $rule = PolicyVersion::findOrFail($submission->policy_version_id)->requirements()->firstOrFail();
        SubmissionRequirement::create(['submission_id' => $submission->id, 'policy_requirement_id' => $rule->id,
            'activity_version_id' => $version->id, 'evidence_status' => 'submitted', 'document_ids' => [], 'rule_snapshot' => ['code' => 'historic-original', 'verdict' => 'complete']]);

        return $submission;
    }

    public function test_original_calendar_preyear_facts_and_new_proposals_have_distinct_treatment(): void
    {
        $original = $this->create();
        $this->assertSame(1, $original->latestVersion->requirement_category);
        $new = $this->create(1, ['calendar_id' => null]);
        $this->assertSame(1, $new->latestVersion->category);
        $this->assertSame(2, $new->latestVersion->requirement_category);
        $latePlan = $this->create(1, ['classification' => ['regular_process_confirmed' => false]]);
        $this->assertSame(2, $latePlan->latestVersion->requirement_category);
        $manual = $this->revise($original, ['category' => 2, 'classification_reason' => '']);
        $this->assertSame(2, $manual->latestVersion->category);
    }

    public function test_schedule_revision_after_receipt_preserves_original_verdict_and_baseline(): void
    {
        $activity = $this->create();
        $receipt = $this->receipt($activity);
        $before = $receipt->fresh()->getAttributes();
        $items = $receipt->requirements->toArray();
        $originalId = $activity->latestVersion->id;
        $revised = $this->revise($activity, ['starts_at' => '2026-11-16 09:01']);
        $assessment = $this->assessment($revised);
        $this->assertSame(2, $revised->latestVersion->requirement_category);
        $this->assertSame(1, $revised->latestVersion->category);
        $this->assertSame($originalId, $assessment->baseline_version_id);
        $this->assertSame($receipt->id, $assessment->baseline_submission_id);
        $this->assertTrue($assessment->timing['immediate_notice_required']);
        $this->assertSame('unresolved', $assessment->timing['rcd_anchor']);
        $this->assertNull($assessment->timing['rcd_due_at']);
        $this->assertSame('historic-original', $assessment->requirements_before[0]['rule_snapshot']['code']);
        $this->assertSame($before, $receipt->fresh()->getAttributes());
        $this->assertSame($items, $receipt->fresh()->requirements->toArray());
    }

    public static function escalationTriggers(): array
    {
        return array_map(fn ($trigger) => [$trigger], ['strenuous', 'requires_accommodation', 'long_distance', 'vulnerable_participants', 'class_size']);
    }

    #[DataProvider('escalationTriggers')]
    public function test_each_category_three_trigger_agrees_with_resolver_and_requires_saa(string $trigger): void
    {
        $activity = $this->create(3);
        $this->assertSame(3, $activity->latestVersion->requirement_category);
        $new = $this->revise($activity, ['conditions' => [$trigger => true]]);
        $this->assertSame(3, $new->latestVersion->category);
        $this->assertSame(2, $new->latestVersion->requirement_category);
        $this->assertSame(2, app(RequirementResolver::class)->requirementCategory(3, $new->latestVersion->conditions, $this->rules));
        $this->assertContains('saa', collect($this->assessment($new)->requirements_after)->pluck('document_code')->all());
    }

    public function test_ordinary_category_three_has_no_saa(): void
    {
        $activity = $this->create(3);
        $this->assertNotContains('saa', collect($this->assessment($activity)->requirements_after)->pluck('document_code')->all());
    }

    public function test_new_activity_after_original_calendar_submission_is_category_two_treatment(): void
    {
        $original = $this->create();
        $receipt = $this->receipt($original);
        $added = $this->create();
        $this->assertSame(2, $added->latestVersion->requirement_category);
        $this->assertSame($receipt->id, $this->assessment($added)->facts['original_calendar_submission_id']);
    }

    public function test_july_calendar_receipt_remains_original_calendar_without_rewriting_its_baseline(): void
    {
        $data = $this->data();
        unset($data['classification']);
        $legacy = app(SaveActivity::class)->execute($this->hei, $data);
        $july = PolicyVersion::where('code', 'LAKBAY-JULY-2026-DRAFT')->firstOrFail();
        $receipt = $this->receipt($legacy, $july->id, 'legacy');
        $before = $receipt->fresh()->getAttributes();
        $added = $this->create();
        $this->assertSame(2, $added->latestVersion->requirement_category);
        $this->assertSame($receipt->id, $this->assessment($added)->facts['original_calendar_submission_id']);
        $this->assertSame($before, $receipt->fresh()->getAttributes());
    }

    public function test_invitation_alone_does_not_reclassify_an_unchanged_original_calendar_activity(): void
    {
        $activity = $this->create();
        $this->receipt($activity);
        $new = $this->revise($activity, ['conditions' => ['external_invitation' => true]]);
        $this->assertSame(1, $new->latestVersion->requirement_category);
        $this->assertFalse($this->assessment($new)->timing['immediate_notice_required']);
        $changed = $this->revise($new, ['description' => 'Different learning scope']);
        $this->assertSame(2, $changed->latestVersion->requirement_category);
    }

    public function test_unapproved_unsubmitted_draft_edit_is_record_only(): void
    {
        $activity = $this->create(1, ['authorization' => ['official' => '', 'date' => '']]);
        $new = $this->revise($activity, ['venue' => 'Alternative draft venue']);
        $assessment = $this->assessment($new);
        $this->assertNull($assessment->baseline_version_id);
        $this->assertSame(1, $new->latestVersion->requirement_category);
        $this->assertFalse($assessment->timing['immediate_notice_required']);
    }

    public function test_personnel_and_count_only_require_rcd_while_contact_only_is_recorded(): void
    {
        $activity = $this->create();
        $this->receipt($activity);
        $new = $this->revise($activity, ['personnel' => [['name' => 'Fictional Replacement PIC']], 'student_count' => 21]);
        $this->assertSame(1, $new->latestVersion->requirement_category);
        $this->assertTrue($this->assessment($new)->timing['immediate_notice_required']);
        $other = $this->create(2);
        $this->receipt($other);
        $contact = $this->revise($other, ['personnel' => [['contact' => 'updated@example.test']]]);
        $this->assertFalse($this->assessment($contact)->timing['immediate_notice_required']);
    }

    public function test_configured_venue_context_requires_reason_and_preserves_category_one(): void
    {
        $activity = $this->create();
        $this->receipt($activity);
        $new = $this->revise($activity, ['venue' => 'Fictional museum, clarified address',
            'classification' => ['contextual_choices' => ['venue' => 'same_destination_address_correction']]]);
        $this->assertSame(1, $new->latestVersion->requirement_category);
        $this->assertSame('rcd_only', $this->assessment($new)->changes['cumulative'][0]['outcome']);
    }

    public function test_a_subminute_schedule_change_has_no_minor_exemption(): void
    {
        $activity = $this->create();
        $this->receipt($activity);
        $new = $this->revise($activity, ['starts_at' => '2026-11-16 09:00:01']);
        $this->assertSame(2, $new->latestVersion->requirement_category);
    }

    public function test_legacy_activity_has_no_retroactive_assessment_until_explicit_revision(): void
    {
        $data = $this->data();
        unset($data['classification']);
        $legacy = app(SaveActivity::class)->execute($this->hei, $data);
        $receipt = $this->receipt($legacy);
        $snapshot = $receipt->fresh()->snapshot;
        $this->assertSame(0, ClassificationAssessment::count());
        $new = $this->revise($legacy, ['venue' => 'Changed legacy venue', 'classification' => ['rule_set_id' => $this->rules->id, 'planned_on' => '2026-09-15', 'regular_process_confirmed' => true]]);
        $this->assertSame(2, $new->latestVersion->requirement_category);
        $this->assertSame($snapshot, $receipt->fresh()->snapshot);
        $this->assertSame(1, ClassificationAssessment::count());
    }

    public function test_new_rule_data_can_change_a_default_without_rewriting_prior_assessments(): void
    {
        $activity = $this->create();
        $this->receipt($activity);
        $before = $this->assessment($activity)->getAttributes();
        $rules = $this->rules->rules;
        $rules['materiality']['venue']['default_outcome'] = 'rcd_only';
        $successor = ClassificationRuleSet::factory()->create(['rules' => $rules, 'supersedes_id' => $this->rules->id]);
        $new = $this->revise($activity, ['venue' => 'Different venue', 'classification' => ['rule_set_id' => $successor->id]]);
        $this->assertSame(1, $new->latestVersion->requirement_category);
        $this->assertSame($before, ClassificationAssessment::findOrFail($before['id'])->getAttributes());
    }

    public function test_candidate_rule_cannot_be_used_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(ValidationException::class);
        $this->create();
    }

    public function test_cross_institution_baseline_is_rejected_and_rolls_back(): void
    {
        $first = $this->create();
        $receipt = $this->receipt($first);
        $second = $this->create(2);
        $this->expectException(ModelNotFoundException::class);
        $this->revise($second, ['classification' => ['baseline_submission_id' => $receipt->id]]);
    }

    public function test_invalid_context_cannot_create_a_schedule_exemption(): void
    {
        $activity = $this->create();
        $this->receipt($activity);
        $this->expectException(ValidationException::class);
        $this->revise($activity, ['starts_at' => '2026-11-16 09:01', 'classification' => ['contextual_choices' => ['schedule' => 'minor_change']]]);
    }

    public function test_claimed_after_conduct_requires_evidence_and_cannot_bypass_before_rules(): void
    {
        $activity = $this->create();
        $this->receipt($activity);
        $this->expectException(ValidationException::class);
        $this->revise($activity, ['venue' => 'Different venue', 'classification' => ['phase' => 'after']]);
    }

    public function test_post_conduct_report_keeps_prior_category_and_unresolved_formal_due(): void
    {
        $activity = $this->create();
        $this->receipt($activity);
        $activity->update(['actual_started_at' => now()->subHours(5), 'actual_ended_at' => now()->subHour()]);
        $new = $this->revise($activity, ['student_count' => 19]);
        $this->assertSame('after', $this->assessment($new)->timing['phase']);
        $this->assertNull($this->assessment($new)->timing['rcd_due_at']);
        $this->assertSame(1, $new->latestVersion->requirement_category);
    }

    private function evidence(Activity $activity): array
    {
        $version = $activity->latestVersion;
        $evidence = [];
        foreach (app(RequirementResolver::class)->resolve($version, $this->rules->policyVersion, 'pre_activity')
            ->merge(app(RequirementResolver::class)->resolve($version, $this->rules->policyVersion, 'requested_evidence')) as $rule) {
            if ($rule->evidence_mode === 'certified_retained') {
                $evidence[$version->id][$rule->id] = ['status' => 'certified_retained', 'reason' => 'Fictional unit-test retention certification'];

                continue;
            }
            $type = DocumentType::findOrFail($rule->requirementDefinition->document_type_id);
            $types = [$type->code, ...($rule->supporting_document_codes ?? [])];
            $ids = [];
            foreach ($types as $code) {
                $type = DocumentType::where('code', $code)->firstOrFail();
                $ids[] = Document::create(['institution_id' => $activity->institution_id, 'document_type_id' => $type->id,
                    'public_id' => (string) Str::uuid(), 'path' => 'unit-test-simulated.pdf', 'original_name' => 'Fictional test evidence.pdf',
                    'mime' => 'application/pdf', 'size' => 9, 'sha256' => hash('sha256', 'fixture'), 'confidentiality' => 'institutional',
                    'scan_status' => 'clean', 'is_signed' => true, 'uploaded_by' => $this->hei->id])->id;
            }
            $evidence[$version->id][$rule->id] = ['status' => 'submitted', 'document_ids' => $ids];
        }

        return $evidence;
    }

    public function test_confirmed_assessment_is_frozen_in_a_successful_submission(): void
    {
        $activity = $this->create(3);
        $submission = app(SubmitActivity::class)->execute($this->hei, [$activity->id], $this->rules->policy_version_id, $this->evidence($activity));
        $this->assertSame($this->assessment($activity)->id, $submission->snapshot['activities'][0]['classification_assessment']['id']);
    }

    public function test_new_proposal_can_submit_category_two_evidence_without_being_in_original_cloca(): void
    {
        $original = $this->create();
        $receipt = $this->receipt($original);
        $new = $this->create();
        $submission = app(SubmitActivity::class)->execute($this->hei, [$new->id], $this->rules->policy_version_id, $this->evidence($new));
        $this->assertSame('pre_activity', $submission->stage);
        $this->assertSame(2, $submission->snapshot['activities'][0]['requirement_category']);
        $this->assertSame($receipt->id, $submission->snapshot['activities'][0]['classification_assessment']['facts']['original_calendar_submission_id']);
    }

    public function test_insufficient_lead_is_flagged_but_does_not_block_complete_late_intake(): void
    {
        config(['lakbay.holiday_calendar_version' => 'slice2-late-intake']);
        Holiday::create(['date' => '2026-11-12', 'name' => 'Fictional QA holiday', 'scope' => 'national', 'calendar_version' => 'slice2-late-intake']);
        $this->travelTo(CarbonImmutable::parse('2026-11-11 09:00', 'Asia/Manila'));
        $activity = $this->create(2);
        $this->assertTrue($this->assessment($activity)->timing['insufficient_lead']);
        $submission = app(SubmitActivity::class)->execute($this->hei, [$activity->id], $this->rules->policy_version_id, $this->evidence($activity));
        $this->assertTrue($submission->is_late);
        $this->assertSame('submitted', $submission->fresh()->status);
    }

    public function test_configuration_can_retain_escalated_treatment_after_triggers_are_removed(): void
    {
        $activity = $this->create(3, ['conditions' => ['strenuous' => true]]);
        $data = $this->rules->rules;
        $data['reversion']['reassess_removed_triggers'] = false;
        $successor = ClassificationRuleSet::factory()->create(['supersedes_id' => $this->rules->id, 'rules' => $data]);
        $new = $this->revise($activity, ['conditions' => ['strenuous' => false], 'classification' => ['rule_set_id' => $successor->id]]);
        $this->assertSame(2, $new->latestVersion->requirement_category);
        $this->assertContains('saa', collect($this->assessment($new)->requirements_after)->pluck('document_code')->all());
    }

    public function test_assessment_referenced_holiday_calendar_cannot_be_rewritten(): void
    {
        config(['lakbay.holiday_calendar_version' => 'slice2-frozen-holidays']);
        $holiday = Holiday::create(['date' => '2026-11-12', 'name' => 'Fictional immutable holiday', 'scope' => 'national', 'calendar_version' => 'slice2-frozen-holidays']);
        $this->create(3);
        $this->expectException(\LogicException::class);
        $holiday->update(['date' => '2026-11-13']);
    }

    public function test_sequential_changes_and_reversion_append_history_without_erasing_events(): void
    {
        $activity = $this->create();
        $receipt = $this->receipt($activity);
        $first = $this->revise($activity, ['venue' => 'Fictional park']);
        $firstAssessment = $this->assessment($first);
        $second = $this->revise($first, ['student_count' => 25]);
        $secondAssessment = $this->assessment($second);
        $this->assertSame($firstAssessment->id, $secondAssessment->previous_assessment_id);
        $this->assertNotEmpty($secondAssessment->changes['cumulative']);
        $this->assertNotEmpty($secondAssessment->changes['sequential']);
        $reverted = $this->revise($second, ['venue' => 'Fictional museum', 'student_count' => 20]);
        $this->assertTrue($this->assessment($reverted)->changes['reversion']);
        $this->assertSame(1, $reverted->latestVersion->requirement_category);
        $this->assertTrue($this->assessment($reverted)->timing['immediate_notice_required']);
        $this->assertSame($receipt->id, $this->assessment($reverted)->baseline_submission_id);
        $this->assertSame(4, ClassificationAssessment::count());
    }

    public function test_trigger_removal_without_confirmation_cannot_be_submitted(): void
    {
        $activity = $this->create(3, ['conditions' => ['strenuous' => true]]);
        $new = $this->revise($activity, ['conditions' => ['strenuous' => false], 'classification' => ['confirm' => false]]);
        $assessment = $this->assessment($new);
        $this->assertTrue($assessment->changes['trigger_removal']);
        $this->assertSame('recommended', $assessment->state);
        $this->expectException(ValidationException::class);
        app(SubmitActivity::class)->execute($this->hei, [$new->id], $this->rules->policy_version_id, []);
    }

    public function test_missing_reason_rolls_back_activity_version_and_audit(): void
    {
        $activity = $this->create();
        $this->receipt($activity);
        try {
            $this->revise($activity, ['venue' => 'Different venue', 'classification' => ['reason' => '']]);
            $this->fail('Reason required.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('classification.reason', $e->errors());
        }
        $this->assertSame(1, $activity->versions()->count());
        $this->assertSame(1, ClassificationAssessment::count());
    }

    public function test_stale_revision_and_omitted_classification_cannot_bypass_assessment(): void
    {
        $activity = $this->create();
        $original = $activity->latestVersion->id;
        $this->revise($activity);
        $this->expectException(ValidationException::class);
        $this->revise($activity, ['classification' => ['expected_version_id' => $original]]);
    }

    public function test_preparer_cannot_confirm_treatment(): void
    {
        $this->hei->syncRoles(['hei_preparer']);
        $this->expectException(HttpException::class);
        $this->create();
    }

    public function test_student_supervision_is_required_before_confirmation(): void
    {
        $this->expectException(ValidationException::class);
        $this->create(3, ['classification' => ['student_supervision_reference' => '']]);
    }

    public function test_missing_planning_evidence_is_not_silently_confirmed(): void
    {
        $this->expectException(ValidationException::class);
        $this->create(1, ['classification' => ['planned_on' => null]]);
    }

    public function test_missing_category_three_trigger_fact_is_not_assumed_false(): void
    {
        $data = $this->data(3);
        unset($data['conditions']['class_size']);
        $this->expectException(ValidationException::class);
        app(SaveActivity::class)->execute($this->hei, $data);
    }

    public function test_trigger_removal_needs_a_reason_to_confirm_ordinary_category_three_again(): void
    {
        $activity = $this->create(3, ['conditions' => ['class_size' => true]]);
        $this->expectException(ValidationException::class);
        $this->revise($activity, ['conditions' => ['class_size' => false], 'classification' => ['reason' => '']]);
    }

    public function test_omitting_assessment_input_on_an_assessed_activity_is_rejected(): void
    {
        $activity = $this->create();
        $data = $this->data();
        unset($data['classification']);
        $this->expectException(ValidationException::class);
        app(SaveActivity::class)->execute($this->hei, $data, $activity);
    }

    public function test_during_conduct_records_change_without_retroactive_category_two(): void
    {
        $activity = $this->create();
        $this->receipt($activity);
        $activity->update(['actual_started_at' => now()]);
        $new = $this->revise($activity, ['venue' => 'Emergency alternative venue']);
        $this->assertSame(1, $new->latestVersion->requirement_category);
        $this->assertSame('during', $this->assessment($new)->timing['phase']);
        $this->assertTrue($this->assessment($new)->timing['immediate_notice_required']);
    }

    public static function leadBoundaries(): array
    {
        return [['2026-11-09 09:00', false], ['2026-11-10 09:00', false], ['2026-11-10 09:00:01', true], ['2026-11-11 09:00', true]];
    }

    #[DataProvider('leadBoundaries')]
    public function test_two_four_and_exact_three_working_days_with_holiday(string $now, bool $late): void
    {
        config(['lakbay.holiday_calendar_version' => 'slice2-holidays']);
        Holiday::create(['date' => '2026-11-12', 'name' => 'Fictional QA holiday', 'scope' => 'national', 'calendar_version' => 'slice2-holidays']);
        $this->travelTo(CarbonImmutable::parse($now, 'Asia/Manila'));
        $activity = $this->create(2);
        $timing = $this->assessment($activity)->timing;
        $this->assertSame('2026-11-10T01:00:00+00:00', $timing['lead_due_at']);
        $this->assertSame($late, $timing['insufficient_lead']);
        $this->assertSame('slice2-holidays', $timing['holiday_calendar_version']);
    }
}
