<?php

namespace App\Filament\Support;

use App\Models\Activity;
use App\Models\ClassificationRuleSet;
use App\Models\Submission;
use App\Services\Compliance\ClassificationPresentation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class ClassificationForms
{
    public static function section(): Section
    {
        return Section::make('Classification assessment and confirmation')
            ->description('Save facts to obtain a recommendation, inspect Classification history, then Confirm saved classification. Candidate rules are local/testing only until verified activation. Sec. 13, PDF pp.6–7; RCD anchor remains unresolved (Sec. 24, PDF p.13).')
            ->schema([
                Select::make('classification.rule_set_id')->label('Eligible classification rules')->options(fn () => ClassificationPresentation::ruleOptions())
                    ->live()->required(fn () => ClassificationPresentation::ruleOptions() !== [])->dehydrated(fn ($state) => filled($state))->helperText('When eligible rules are configured, select them before saving. Explicit selection enrolls this plan; historical submissions are unchanged. No eligible options means policy/configuration prerequisites are unavailable.'),
                Hidden::make('classification.expected_version_id'),
                DatePicker::make('classification.planned_on')->label('Original planning date'),
                Toggle::make('classification.regular_process_confirmed')->label('Regular institutional process')->default(false),
                Textarea::make('classification.student_supervision_reference')->label('Faculty / HEI supervision and approval reference'),
                Select::make('classification.phase')->options(['before' => 'Before conduct', 'during' => 'During actual conduct', 'after' => 'After actual conduct'])->default('before'),
                DateTimePicker::make('classification.conduct_at')->label('Actual conduct date/time (if applicable)'),
                Textarea::make('classification.conduct_reference')->label('Actual conduct evidence reference'),
                Textarea::make('classification.reason')->label('Change / applicability explanation')->maxLength(4000),
                Select::make('classification.baseline_submission_id')->label('Original receipt (optional explicit selection)')
                    ->options(fn ($record) => $record instanceof Activity ? Submission::where('institution_id', $record->institution_id)->whereHas('activityVersions', fn ($query) => $query->where('activity_versions.activity_id', $record->id))->whereNotNull('submitted_at')->pluck('reference', 'id') : []),
                Select::make('classification.approved_baseline_version_id')->label('Approved baseline version (if no receipt)')
                    ->options(fn ($record) => $record instanceof Activity ? $record->versions()->pluck('version', 'id') : []),
                ...self::contextFields(),
            ])->columns(2)->columnSpanFull();
    }

    private static function contextFields(): array
    {
        $groups = [];
        foreach (ClassificationPresentation::ruleOptions() as $id => $label) {
            $set = ClassificationRuleSet::findOrFail($id);
            $groups = array_unique([...$groups, ...array_keys($set->configuration()['revision']['contextual_choices'] ?? [])]);
        }

        return array_map(fn ($group) => Select::make('classification.contextual_choices.'.$group)
            ->label('Applicability context: '.str_replace('_', ' ', $group))
            ->options(function (Get $get) use ($group) {
                $id = $get('classification.rule_set_id');
                $choices = $id ? (ClassificationRuleSet::find($id)?->configuration()['revision']['contextual_choices'][$group] ?? []) : [];

                return array_combine($choices, array_map(fn ($choice) => str_replace('_', ' ', $choice), $choices));
            })->dehydrated(fn ($state) => filled($state))->helperText('Optional bounded interpretation from the selected rules. Explain it above; defaults apply if blank.'), $groups);
    }
}
