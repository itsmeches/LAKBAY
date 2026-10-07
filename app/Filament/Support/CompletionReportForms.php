<?php

namespace App\Filament\Support;

use App\Models\ActivityVersion;
use App\Models\Document;
use App\Models\IncidentReport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class CompletionReportForms
{
    public static function source(): array
    {
        return [Section::make('1. Activity and proposed information')->description('Choose the exact activity-plan revision being reported. Its proposed dates, office and PIC/APIC are preserved; enter actual venue and participant totals below.')
            ->schema([Fields::institution(), Fields::relation('activity_version_id', ActivityVersion::class)->label('Activity plan revision')->live()
                ->afterStateUpdated(function ($state, Set $set): void {
                    if (! $state || ! array_key_exists((int) $state, Fields::related(ActivityVersion::class))) {
                        return;
                    }
                    $version = ActivityVersion::findOrFail($state);
                    $set('details.venue', $version->venue);
                    $set('details.student_count', $version->student_count);
                    $set('details.other_count', $version->other_count ?? 0);
                    $set('details.ended_at', $version->activity->actual_ended_at?->timezone(config('lakbay.timezone'))->format('Y-m-d H:i:s'));
                })])->columns(2)->columnSpanFull()];
    }

    public static function details(?int $institutionId = null, ?int $activityId = null): array
    {
        return [
            Section::make('2. Implementation and outcome')->schema([
                TextInput::make('title')->label('Draft title')->required()->maxLength(255),
                DateTimePicker::make('details.ended_at')->label('Actual conclusion')->helperText('Asia/Manila; the 72-hour report clock uses actual conclusion, not the proposed end date. Saving a draft does not record completion.'),
                Textarea::make('details.venue')->label('Actual venue / locations')->maxLength(1000),
                TextInput::make('details.student_count')->label('Actual student participants')->numeric()->minValue(0)->maxValue(1000000),
                TextInput::make('details.other_count')->label('Actual other participants')->numeric()->minValue(0)->maxValue(1000000)->default(0),
                Select::make('details.implementation')->label('Status of implementation')->options(['as_authorized' => 'Conducted as approved by HEI', 'with_changes' => 'Conducted with changes'])->live(),
                Textarea::make('details.change_explanation')->label('Brief explanation of changes')->maxLength(3000)->helperText('Required for printing when conducted with changes. A separate RCD remains subject to its own workflow.')->columnSpanFull(),
                Textarea::make('details.summary')->label('Summary of implementation and outcome')->maxLength(6000)->rows(4)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('3. Incidents encountered')->description('Keep the brief explanation factual and free of names, medical details and other personal information. Incident reports remain separate protected records.')
                ->schema([
                    Toggle::make('details.incidents_encountered')->label('Incidents encountered')->default(false)->live(),
                    Textarea::make('details.incident_explanation')->label('Brief incident explanation')->maxLength(3000)->helperText('Required for printing if incidents were encountered.'),
                    Select::make('details.incident_report_ids')->label('Linked incident reports / initial notices')->multiple()->searchable()
                        ->options(function (Get $get) use ($institutionId, $activityId): array {
                            $ids = array_keys(Fields::related(IncidentReport::class, 'type', $institutionId ?? ($get('institution_id') ? (int) $get('institution_id') : null)));
                            $selectedActivity = $activityId;
                            if (! $selectedActivity && $get('activity_version_id') && array_key_exists((int) $get('activity_version_id'), Fields::related(ActivityVersion::class))) {
                                $selectedActivity = ActivityVersion::find($get('activity_version_id'))?->activity_id;
                            }

                            return IncidentReport::whereIn('id', $ids)->when($selectedActivity, fn ($q) => $q->where('activity_id', $selectedActivity))
                                ->get()->mapWithKeys(fn ($report) => [$report->id => $report->type.' (#'.$report->id.')'])->all();
                        })->helperText('Links are preserved internally, not embedded as incident files in the printed ROC. Complete/upload Annex H separately as applicable.'),
                ])->columns(2)->columnSpanFull(),
            Section::make('4. Certification and letterhead')->schema([
                TextInput::make('details.official')->label('Authorized HEI official - complete name')->maxLength(150),
                TextInput::make('details.position')->label('Designation / position')->maxLength(150),
                DatePicker::make('details.issued_on')->label('Issued on (optional; unknown date prints blank)'),
                Textarea::make('details.letterhead_text')->label('HEI letterhead text (optional)')->maxLength(1000),
                Select::make('details.letterhead_document_id')->label('Letterhead image (optional)')->searchable()
                    ->options(fn (Get $get) => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $institutionId ?? ($get('institution_id') ? (int) $get('institution_id') : null))))
                        ->where('confidentiality', 'institutional')->whereIn('mime', ['image/png', 'image/jpeg'])->where('size', '<=', 2 * 1024 * 1024)->pluck('original_name', 'id')->all()),
            ])->description('Print, sign externally, upload the signed PDF as a Completion report, wait for a clean scan, then use File signed ROC. Printing does not certify truth, signatory authority or receipt by CHED.')->columns(2)->columnSpanFull(),
        ];
    }
}
