<?php

namespace App\Filament\Support;

use App\Models\ActivityVersion;
use App\Models\Document;
use App\Models\IncidentReport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class IncidentReportForms
{
    public static function source(): array
    {
        return [Section::make('1. Activity and incident reference')->description('Report promptly; do not wait for the activity to finish or for signatures/evidence. Basic proposed information is preserved from the selected activity revision.')
            ->schema([
                Fields::institution(),
                Fields::relation('activity_version_id', ActivityVersion::class)->label('Activity plan revision')->live(),
                Select::make('supplements_report_id')->label('Supplement to an existing incident (optional)')->searchable()->live()
                    ->options(function (Get $get): array {
                        $ids = array_keys(Fields::related(IncidentReport::class, 'type', $get('institution_id') ? (int) $get('institution_id') : null));
                        $versionId = $get('activity_version_id');
                        $activityId = $versionId && array_key_exists((int) $versionId, Fields::related(ActivityVersion::class))
                            ? ActivityVersion::find($versionId)?->activity_id : null;

                        return IncidentReport::whereIn('id', $ids)->when($activityId, fn ($q) => $q->where('activity_id', $activityId))
                            ->get()->mapWithKeys(fn ($report) => [$report->id => '#'.$report->id.' / '.$report->type.' / '.$report->occurred_at->timezone(config('lakbay.timezone'))->format('d M Y H:i')])->all();
                    })
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if ($state && array_key_exists((int) $state, Fields::related(IncidentReport::class, 'type'))) {
                            $set('details.occurred_at', IncidentReport::findOrFail($state)->occurred_at->timezone(config('lakbay.timezone'))->format('Y-m-d H:i:s'));
                        }
                    })->helperText('Use a new supplement for updates or a signed follow-up. The original occurrence/deadline is retained. Leave blank only for a separate incident.'),
            ])->columns(2)->columnSpanFull()];
    }

    public static function details(?int $institutionId = null): array
    {
        return [
            Section::make('2. Incident information')->schema([
                TextInput::make('title')->label('Preparation title')->required()->maxLength(255),
                DateTimePicker::make('details.occurred_at')->label('Incident date and time')->helperText('Asia/Manila. CMO 24 uses 48 elapsed hours from occurrence; historical policies retain their own rules.'),
                TextInput::make('details.location')->label('Exact incident location')->maxLength(1000),
                TextInput::make('details.type')->label('Type of incident')->maxLength(255)
                    ->helperText('Examples: Injury, Illness, Vehicular Accident, Missing Student/Individual, Security Incident, Natural Hazard, Property Damage.'),
            ])->columns(2)->columnSpanFull(),
            Section::make('3. Persons involved')->description('Record only necessary incident information. This is not a master student or medical-history registry.')
                ->schema([Repeater::make('details.persons')->schema([
                    TextInput::make('name')->required()->maxLength(150),
                    TextInput::make('role')->required()->maxLength(150),
                    Textarea::make('remarks')->maxLength(1000),
                ])->defaultItems(0)->maxItems(50)->columns(3)->columnSpanFull()])->columnSpanFull(),
            Section::make('4. Description, immediate actions and current status')->schema([
                Textarea::make('details.description')->label('Brief description')->maxLength(6000)->rows(3),
                Textarea::make('details.immediate_actions')->label('Immediate actions taken')->maxLength(6000)->rows(3),
                Textarea::make('details.current_status')->label('Current status')->maxLength(3000)->rows(3),
            ])->description('Initial intake needs occurrence, location, type and these factual summaries. State what remains unknown rather than inventing information. Signatory/supporting information can follow in a supplement.')->columnSpanFull(),
            Section::make('5. Supporting documents')->schema([
                Textarea::make('details.supporting_details')->label('Brief details of supporting documents / availability')->maxLength(3000),
                Select::make('details.supporting_document_ids')->label('Available clean supporting files (optional)')->multiple()->searchable()->maxItems(25)
                    ->options(fn (Get $get) => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $institutionId ?? ($get('institution_id') ? (int) $get('institution_id') : null))))
                        ->where('confidentiality', '!=', 'restricted')->pluck('original_name', 'id')->all())
                    ->helperText('Upload personal incident evidence under an appropriately sensitive document type. Files remain separate protected attachments; do not delay an initial notice for unavailable evidence.'),
            ])->columnSpanFull(),
            Section::make('6. Certification and letterhead')->schema([
                TextInput::make('details.official')->label('Authorized HEI official - complete name')->maxLength(150),
                TextInput::make('details.position')->label('Designation / position')->maxLength(150),
                DatePicker::make('details.issued_on')->label('Issued on (optional; unknown date prints blank)'),
                Textarea::make('details.letterhead_text')->label('HEI letterhead text (optional)')->maxLength(1000),
                Select::make('details.letterhead_document_id')->label('Clean letterhead image (optional)')->searchable()
                    ->options(fn (Get $get) => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $institutionId ?? ($get('institution_id') ? (int) $get('institution_id') : null))))
                        ->where('confidentiality', 'institutional')->whereIn('mime', ['image/png', 'image/jpeg'])->where('size', '<=', 2 * 1024 * 1024)->pluck('original_name', 'id')->all()),
            ])->description('A saved preparation is not received by CHED. Send an initial notice promptly, or print/sign externally and upload a clean signed Incident report PDF before signed filing.')->columns(2)->columnSpanFull(),
        ];
    }
}
