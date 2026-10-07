<?php

namespace App\Filament\Ched\Pages;

use App\Actions\Documents\RegisterPublicTemplate;
use App\Actions\Submissions\ConfirmPolicy;
use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\Holiday;
use App\Models\PolicyRequirement;
use App\Models\PolicyVersion;
use App\Services\Access;
use App\Services\Compliance\RequirementResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PolicyConfiguration extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.policy';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('policy.manage') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copy_policy')->label('Create editable policy version')->schema([TextInput::make('code')->required(), TextInput::make('title')->required(), Select::make('source_id')->options(fn () => PolicyVersion::pluck('title', 'id'))->required()])->action(function (array $data) {
                DB::transaction(function () use ($data) {
                    $source = PolicyVersion::findOrFail($data['source_id']);
                    $p = PolicyVersion::create(['code' => $data['code'], 'title' => $data['title'], 'status' => $source->status === 'candidate' ? 'candidate' : 'draft',
                        'issued_on' => $source->issued_on, 'source_sha256' => $source->source_sha256, 'parameters' => $source->parameters]);
                    foreach ($source->requirements as $r) {
                        PolicyRequirement::create($r->only(['requirement_definition_id', 'category', 'predicate', 'evidence_mode', 'deadline_type', 'deadline_anchor', 'deadline_amount', 'source_reference', 'section_row', 'stage', 'signature_mode', 'deadline_status', 'supporting_document_codes']) + ['policy_version_id' => $p->id]);
                    }foreach (FormTemplate::where('policy_version_id', $source->id)->get() as $t) {
                        FormTemplate::create($t->only(['code', 'title', 'version', 'annex', 'source_page', 'path', 'metadata']) + ['policy_version_id' => $p->id]);
                    }
                });
            }),
            Action::make('configure_rule')->schema([
                Select::make('rule_id')->options(fn () => PolicyRequirement::whereHas('policyVersion', fn ($q) => $q->where('status', '!=', 'confirmed'))->with('requirementDefinition', 'policyVersion')->get()->mapWithKeys(fn ($r) => [$r->id => $r->policyVersion->code.' / Cat '.$r->category.' / '.$r->requirementDefinition->title]))->required(),
                Select::make('predicate')->options(array_combine(RequirementResolver::PREDICATES, RequirementResolver::PREDICATES))->required(),
                Select::make('evidence_mode')->options(['submitted' => 'Submitted', 'certified_retained' => 'Certified as retained'])->required(),
                Select::make('stage')->options(['annual_calendar' => 'Annual calendar', 'pre_activity' => 'Pre-activity', 'post_activity' => 'Post-activity', 'requested_evidence' => 'Requested evidence'])->required(),
                Select::make('signature_mode')->options(['signed' => 'Signed official PDF', 'copy' => 'Copy / supporting evidence'])->required(),
                Select::make('deadline_status')->options(['confirmed' => 'Established', 'unresolved' => 'Needs CHED clarification', 'on_request' => 'On request'])->required(),
                Select::make('deadline_type')->options(['calendar_days_before' => 'Calendar days before', 'working_days_before' => 'Working days before', 'hours_after' => 'Elapsed hours after', 'on_request' => 'On request'])->required(),
                Select::make('deadline_anchor')->options(['academic_year_start' => 'Academic-year start', 'activity_start' => 'Activity start', 'actual_conclusion' => 'Actual conclusion', 'incident_occurrence' => 'Incident occurrence', 'request_receipt' => 'Request receipt', 'unresolved' => 'Unresolved'])->required(),
                TextInput::make('deadline_amount')->numeric()->minValue(0)->maxValue(365)->required(), TextInput::make('source_reference')->required(),
            ])->action(function (array $data) {
                $rule = PolicyRequirement::findOrFail($data['rule_id']);
                $rule->update(collect($data)->except('rule_id')->all());
            }),
            Action::make('public_template')->label('Register blank public template')->schema([Select::make('template_id')->options(fn () => FormTemplate::whereHas('policyVersion', fn ($q) => $q->where('status', 'draft'))->get()->mapWithKeys(fn ($t) => [$t->id => $t->code.' / '.$t->title]))->required(), Select::make('document_id')->options(fn () => app(Access::class)->scope(Document::query(), auth()->user())->where('confidentiality', 'institutional')->where('is_signed', false)->where('scan_status', 'clean')->pluck('original_name', 'id'))->required(), Toggle::make('blank_no_personal_data')->label('I verified this is an approved blank template with no personal or institutional submission data')->accepted()])->action(fn (array $data) => app(RegisterPublicTemplate::class)->execute(auth()->user(), FormTemplate::findOrFail($data['template_id']), (int) $data['document_id'])),
            Action::make('confirm_policy')->label('Record CHED-confirmed baseline')->requiresConfirmation()->modalDescription('Only record confirmation after CHED has resolved the requirements and supplied an authoritative baseline. This action freezes the version.')->schema([
                Select::make('policy_id')->options(fn () => PolicyVersion::where('status', '!=', 'confirmed')->pluck('title', 'id'))->required(),
                DatePicker::make('publication_on')->helperText('CMO 24 requires verified publication. Issuance is not publication.'),
                TextInput::make('publication_reference'), DatePicker::make('effective_on')->helperText('CMO 24 effectivity is calculated at publication +15 days.'),
                TextInput::make('confirmation_reference')->required(), Toggle::make('confirmed_by_ched')->accepted(),
            ])->action(function (array $data) {
                $p = PolicyVersion::findOrFail($data['policy_id']);
                app(ConfirmPolicy::class)->execute(auth()->user(), $p, $data);
            }),
            Action::make('holiday')->label('Record holiday')->schema([DatePicker::make('date')->required(), TextInput::make('name')->required(), Select::make('scope')->options(['national' => 'National', 'local' => 'Local'])->required(), TextInput::make('locality'), TextInput::make('calendar_version')->required(), TextInput::make('source_reference')->required()])->action(fn (array $data) => Holiday::create($data)),
        ];
    }

    protected function getViewData(): array
    {
        return ['policies' => PolicyVersion::withCount('requirements')->get(), 'calendarVersion' => config('lakbay.holiday_calendar_version')];
    }
}
