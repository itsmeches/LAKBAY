<?php

namespace Tests\Feature;

use App\Actions\Activities\ConfirmActivityClassification;
use App\Actions\Activities\SaveActivity;
use App\Actions\Activities\SaveCertificationDraft;
use App\Actions\Submissions\SubmitActivity;
use App\Filament\Ched\Resources\Pages\ViewSubmission;
use App\Filament\Hei\Resources\Pages\EditActivity;
use App\Filament\Hei\Resources\Pages\ListActivity;
use App\Filament\Hei\Resources\Pages\ViewActivity;
use App\Jobs\GenerateExport;
use App\Jobs\NotifyInstitution;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\CertificationDraft;
use App\Models\ClassificationRuleSet;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Export;
use App\Models\Institution;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Services\Compliance\ClassificationPresentation;
use App\Services\Compliance\RequirementResolver;
use App\Services\Reporting\RegionalReportBuilder;
use Carbon\CarbonImmutable;
use Database\Seeders\ClassificationEvaluationRuleSeeder;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ClassificationInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    private Institution $institution;

    private AcademicPeriod $period;

    private ClassificationRuleSet $rules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-01 09:00', 'Asia/Manila'));
        $this->seed();
        $this->seed(ClassificationEvaluationRuleSeeder::class);
        Queue::fake();
        config(['lakbay.allow_draft_policy' => true]);
        $this->rules = ClassificationRuleSet::where('code', 'CMO24-GAP2-EVALUATION-V2')->firstOrFail();
        $this->institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'UI-TEST', 'name' => 'Fictional UI HEI', 'institution_type' => 'private', 'address' => 'Fictional address']);
        $this->period = AcademicPeriod::create(['institution_id' => $this->institution->id, 'label' => 'AY', 'starts_on' => '2026-11-01', 'ends_on' => '2027-10-31']);
        $this->hei = User::factory()->create(['app_authentication_secret' => 'TESTSECRET']);
        $this->hei->assignRole('hei_submitter');
        $this->hei->institutions()->attach($this->institution->id, ['position' => 'Submitter', 'verified_at' => now(), 'is_authorized' => true]);
        $this->actingAs($this->hei);
        Filament::setCurrentPanel(Filament::getPanel('hei'));
    }

    private function data(): array
    {
        return ['institution_id' => $this->institution->id, 'academic_period_id' => $this->period->id, 'title' => 'Fictional supervised study', 'category' => 3,
            'academic_nature' => 'curricular', 'activity_type' => 'Study', 'initiator' => 'student', 'description' => 'Study local services', 'learning_objectives' => 'Apply learning',
            'responsible_office' => 'OSAS', 'starts_at' => '2026-11-16 09:00', 'ends_at' => '2026-11-16 17:00', 'venue' => 'Fictional venue', 'student_count' => 20, 'other_count' => 0,
            'conditions' => ['strenuous' => false, 'requires_accommodation' => false, 'long_distance' => false, 'vulnerable_participants' => false, 'class_size' => false, 'requires_physical_activity' => false, 'requires_travel' => false],
            'authorization' => ['official' => 'Faculty', 'date' => '2026-09-30'],
            'personnel' => [['role' => 'supervisor', 'name' => 'Fictional supervisor', 'position' => 'Faculty', 'contact' => 'faculty@example.test']],
            'classification' => ['rule_set_id' => $this->rules->id, 'student_supervision_reference' => 'Faculty approval', 'reason' => 'Facts recorded for review']];
    }

    public function test_actual_create_form_saves_recommendation_and_record_action_confirms_it(): void
    {
        Livewire::test(ListActivity::class)->callAction('create', data: $this->data())->assertHasNoActionErrors();
        $activity = Activity::firstOrFail();
        $this->assertSame('recommended', ClassificationPresentation::latest($activity)->state);
        Livewire::test(EditActivity::class, ['record' => $activity->id])->assertSee('Classification assessment and confirmation');
        Livewire::test(ViewActivity::class, ['record' => $activity->id])->callAction('confirm_classification', data: ['expected_version_id' => $activity->latestVersion->id, 'reason' => 'Authorized confirmation'])->assertHasNoActionErrors();
        $this->assertSame('confirmed', ClassificationPresentation::latest($activity->fresh())->state);
        $component = Livewire::test(ViewActivity::class, ['record' => $activity->id])->mountAction('classification_history')->assertActionMounted('classification_history');
        $html = $component->instance()->getMountedActions()[0]->getSchema(Schema::make($component->instance()))->toHtml();
        $this->assertStringContainsString('requirement treatment Category 3', $html);
        $this->assertStringContainsString('unresolved', $html);
    }

    public function test_confirmation_rejects_duplicate_and_unauthorized_institution(): void
    {
        $activity = app(SaveActivity::class)->execute($this->hei, $this->data());
        $versionId = $activity->latestVersion->id;
        app(ConfirmActivityClassification::class)->execute($this->hei, $activity, $versionId, 'Authorized confirmation');
        try {
            app(ConfirmActivityClassification::class)->execute($this->hei, $activity, $versionId, 'Repeated confirmation');
            $this->fail('Repeated confirmation must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        $outsider = User::factory()->create();
        $outsider->assignRole('hei_submitter');
        $this->expectException(HttpException::class);
        app(ConfirmActivityClassification::class)->execute($outsider, $activity, $versionId, 'Unauthorized');
    }

    public function test_candidate_rules_are_not_offered_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->assertSame([], ClassificationPresentation::ruleOptions());
    }

    public function test_ched_review_and_report_use_frozen_assessment_not_current_revision(): void
    {
        $activity = app(SaveActivity::class)->execute($this->hei, $this->data());
        $activity = app(ConfirmActivityClassification::class)->execute($this->hei, $activity, $activity->latestVersion->id, 'Filed confirmation');
        $version = $activity->latestVersion->toArray() + ['classification_assessment' => ClassificationPresentation::latest($activity)->toArray()];
        $submission = Submission::create(['institution_id' => $this->institution->id, 'policy_version_id' => $this->rules->policy_version_id, 'type' => 'activity', 'reference' => 'FICTIONAL-UI-RECEIPT', 'revision' => 1, 'public_id' => (string) Str::uuid(), 'stage' => 'pre_activity', 'status' => 'submitted', 'submitted_by' => $this->hei->id, 'submitted_at' => now(), 'received_at' => now(), 'snapshot' => ['activities' => [$version], 'evidence' => [], 'documents' => []], 'holiday_calendar_version' => 'PH-2026']);
        $data = $this->data();
        $data['classification']['expected_version_id'] = $activity->latestVersion->id;
        $data['description'] = 'Later scope';
        app(SaveActivity::class)->execute($this->hei, $data, $activity);
        $reviewer = User::factory()->create(['region_id' => $this->institution->region_id, 'app_authentication_secret' => 'TESTSECRET']);
        $reviewer->assignRole('ched_reviewer');
        $this->actingAs($reviewer);
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        $component = Livewire::test(ViewSubmission::class, ['record' => $submission->id])->mountAction('submitted_classification')->assertActionMounted('submitted_classification');
        $html = $component->instance()->getMountedActions()[0]->getSchema(Schema::make($component->instance()))->toHtml();
        $this->assertStringContainsString('requirement treatment Category 3', $html);
        $this->assertStringContainsString('Filed confirmation', $html);
        $analyst = User::factory()->create(['region_id' => $this->institution->region_id]);
        $analyst->assignRole('ched_analyst');
        $report = app(RegionalReportBuilder::class)->build($analyst);
        $this->assertSame($version['classification_assessment']['id'], $report['activities'][0]['classification_assessment']['id']);
        $this->assertSame(1, $report['totals']['activities']);
    }

    public function test_preparer_cannot_confirm(): void
    {
        $activity = app(SaveActivity::class)->execute($this->hei, $this->data());
        $this->hei->syncRoles(['hei_preparer']);
        Livewire::test(ViewActivity::class, ['record' => $activity->id])->assertActionHidden('confirm_classification');
    }

    private function evidence(Activity $activity): array
    {
        $version = $activity->latestVersion;
        $resolver = app(RequirementResolver::class);
        $evidence = [];
        foreach ($resolver->resolve($version, $this->rules->policyVersion, 'pre_activity')->merge($resolver->resolve($version, $this->rules->policyVersion, 'requested_evidence')) as $rule) {
            if ($rule->evidence_mode === 'certified_retained') {
                $evidence[$version->id][$rule->id] = ['status' => 'certified_retained', 'reason' => 'Fictional retention certification'];

                continue;
            }
            $ids = [];
            foreach ([$rule->requirementDefinition->documentType->code, ...($rule->supporting_document_codes ?? [])] as $code) {
                $ids[] = Document::create(['institution_id' => $activity->institution_id, 'document_type_id' => DocumentType::where('code', $code)->firstOrFail()->id,
                    'public_id' => (string) Str::uuid(), 'path' => 'simulated.pdf', 'original_name' => 'Fictional unit-test original.pdf', 'mime' => 'application/pdf', 'size' => 9, 'sha256' => hash('sha256', 'fixture'),
                    'confidentiality' => 'institutional', 'scan_status' => 'clean', 'is_signed' => true, 'uploaded_by' => $this->hei->id])->id;
            }
            $evidence[$version->id][$rule->id] = ['status' => 'submitted', 'document_ids' => $ids];
        }

        return $evidence;
    }

    private function certification(): CertificationDraft
    {
        $items = collect(config('certification.rows'))->map(fn ($row) => ['status' => 'attested', 'supporting' => array_fill_keys(array_keys($row['supporting']), true)])->all();
        $items['authorization'] = ['status' => 'not_applicable', 'reason' => 'Ordinary Category 3 does not require SAA', 'supporting' => ['saa' => false]];

        return app(SaveCertificationDraft::class)->execute($this->hei, ['institution_id' => $this->institution->id, 'academic_period_id' => $this->period->id, 'title' => 'Fictional certification', 'details' => ['official' => 'Official', 'position' => 'Head', 'items' => $items]]);
    }

    public function test_filed_notice_annex_f_and_csv_preserve_the_confirmed_treatment(): void
    {
        Storage::fake('private');
        $activity = app(SaveActivity::class)->execute($this->hei, $this->data());
        $activity = app(ConfirmActivityClassification::class)->execute($this->hei, $activity, $activity->latestVersion->id, 'Original confirmed facts');
        $draft = $this->certification();
        $submission = app(SubmitActivity::class)->execute($this->hei, [$activity->id], $this->rules->policy_version_id, $this->evidence($activity), certificationDraftId: $draft->id);
        Queue::assertPushed(NotifyInstitution::class, fn ($job) => str_contains($job->message, 'confirmed requirement Category 3'));
        $this->assertSame(3, $submission->snapshot['annex_f']['classification_context'][0]['requirement_category']);
        $this->get(route('certification.submission', $submission->public_id))->assertOk()->assertSee('Application classification context')->assertSee('SAA and row-12 PIC designation are not required.');
        $pdf = $this->get(route('certification.submission', ['publicId' => $submission->public_id, 'download' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $analyst = User::factory()->create(['region_id' => $this->institution->region_id]);
        $analyst->assignRole('ched_analyst');
        $export = Export::create(['requested_by' => $analyst->id, 'filters' => ['format' => 'csv']]);
        (new GenerateExport($export->id))->handle();
        $csv = Storage::disk('private')->get($export->fresh()->path);
        $this->assertStringContainsString('Assessment state', $csv);
        $this->assertStringContainsString('Original confirmed facts', $csv);
        $this->assertStringContainsString('confirmed', $csv);
        $pdfExport = Export::create(['requested_by' => $analyst->id, 'filters' => ['format' => 'pdf']]);
        (new GenerateExport($pdfExport->id))->handle();
        $this->assertStringStartsWith('%PDF-', Storage::disk('private')->get($pdfExport->fresh()->path));
    }

    public function test_escalated_annex_f_cannot_claim_saa_is_not_applicable(): void
    {
        $data = $this->data();
        $data['conditions']['strenuous'] = true;
        $data['personnel'] = [['role' => 'pic', 'name' => 'Fictional PIC', 'position' => 'Faculty', 'contact' => 'pic@example.test', 'hei_employed' => true, 'accompanies_activity' => true, 'qualifications' => 'Relevant experience', 'first_aid_trained' => true, 'first_aid_reference' => 'Fictional reference']];
        $activity = app(SaveActivity::class)->execute($this->hei, $data);
        $activity = app(ConfirmActivityClassification::class)->execute($this->hei, $activity, $activity->latestVersion->id, 'Escalated confirmed facts');
        $draft = $this->certification();
        $this->expectException(ValidationException::class);
        app(SubmitActivity::class)->execute($this->hei, [$activity->id], $this->rules->policy_version_id, $this->evidence($activity), certificationDraftId: $draft->id);
    }
}
