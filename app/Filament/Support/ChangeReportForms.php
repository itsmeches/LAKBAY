<?php

namespace App\Filament\Support;

use App\Models\ActivityVersion;
use App\Models\ChangeReport;
use App\Models\Document;
use App\Models\Submission;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

class ChangeReportForms
{
    public static function source(): array
    {
        return [Section::make('1. Original submission and activity revisions')->description('Report changes whenever they arise, including before the activity. Preparation is not a CHED receipt.')
            ->schema([
                Fields::institution(),
                Fields::relation('original_version_id', ActivityVersion::class)->label('Previously submitted activity revision')->live(),
                Select::make('revised_version_id')->label('Revised activity revision')->required()->searchable()->live()->options(function (Get $get): array {
                    $ids = array_keys(Fields::related(ActivityVersion::class));
                    $old = in_array((int) $get('original_version_id'), $ids, true) ? ActivityVersion::find($get('original_version_id')) : null;

                    return $old ? ActivityVersion::whereIn('id', $ids)->where('activity_id', $old->activity_id)->where('version', '>', $old->version)->get()->mapWithKeys(fn ($v) => [$v->id => $v->title.' / revision '.$v->version])->all() : [];
                }),
                Select::make('original_submission_id')->label('Original electronic submission receipt (optional)')->searchable()->live()->options(function (Get $get): array {
                    $ids = array_keys(Fields::related(Submission::class, 'reference'));
                    $versionId = (int) $get('original_version_id');

                    return array_key_exists($versionId, Fields::related(ActivityVersion::class)) ? Submission::whereIn('id', $ids)->whereNotNull('submitted_at')
                        ->whereHas('activityVersions', fn ($q) => $q->where('activity_versions.id', $versionId))->pluck('reference', 'id')->all() : [];
                })->helperText('Includes historical receipts. External date/reference must be blank when an electronic receipt is selected.'),
                Select::make('supplements_report_id')->label('Follow-up to existing change notice (optional)')->searchable()->options(function (Get $get): array {
                    return ChangeReport::whereIn('id', array_keys(Fields::related(ChangeReport::class, 'reason')))
                        ->where('original_version_id', $get('original_version_id'))->where('revised_version_id', $get('revised_version_id'))
                        ->get()->mapWithKeys(fn ($r) => [$r->id => '#'.$r->id.' / '.$r->intake_kind.' / '.$r->changed_at->timezone(config('lakbay.timezone'))->format('d M Y H:i')])->all();
                })->helperText('Retain the same revision pair, change time and original submission reference; a new change needs a separate notice.'),
            ])->columns(2)->columnSpanFull()];
    }

    public static function details(?int $institutionId = null): array
    {
        $signatories = [];
        foreach (['prepared' => 'Prepared by', 'reviewed' => 'Reviewed by - authorized HEI official', 'noted' => 'Noted by - authorized HEI official'] as $key => $label) {
            $signatories[] = TextInput::make('details.'.$key.'_name')->label($label)->maxLength(150);
            $signatories[] = TextInput::make('details.'.$key.'_position')->label($label.' designation / position')->maxLength(150);
        }

        return [
            Section::make('2. Change information')->schema([
                TextInput::make('title')->label('Preparation title')->required()->maxLength(255),
                DateTimePicker::make('details.changed_at')->label('When the change occurred')->helperText('Asia/Manila. Notify CHED immediately. Formal CMO 72-hour deadline anchor remains unresolved.'),
                DatePicker::make('details.original_submitted_on')->label('External original submission date (if no electronic receipt)'),
                TextInput::make('details.original_submission_reference')->label('External original submission reference / receipt evidence')->maxLength(255)
                    ->helperText('HEI-entered external reference; it does not establish an electronic CHED receipt.'),
                Textarea::make('details.reason')->label('III. Reason for change')->maxLength(6000)->rows(4)->columnSpanFull(),
                Repeater::make('details.other_changes')->label('II. Additional Other particulars')->schema([
                    TextInput::make('label')->label('Particular')->required()->maxLength(150),
                    Textarea::make('previous')->label('Previously submitted')->required()->maxLength(1000),
                    Textarea::make('revised')->label('Revised information')->required()->maxLength(1000),
                ])->defaultItems(0)->maxItems(15)->columns(3)->columnSpanFull()
                    ->helperText('Core comparison and recorded itinerary/activity changes come from the revisions. Add only other particulars.'),
            ])->columns(2)->columnSpanFull(),
            Section::make('3. Supporting documents')->schema([
                Textarea::make('details.supporting_details')->label('IV. Brief details and availability of supporting documents')->maxLength(3000),
                Select::make('details.supporting_document_ids')->label('Clean institutional supporting files (optional)')->multiple()->searchable()->maxItems(25)
                    ->options(fn (Get $get) => self::documents($institutionId, $get)->pluck('original_name', 'id')->all())
                    ->helperText('Attachments remain private and separate. Use Annex H for confidential incident details. Do not delay immediate notice for evidence.'),
            ])->columnSpanFull(),
            Section::make('4. Prepared, reviewed and noted by')->schema([
                ...$signatories,
                TextInput::make('details.prepared_contact')->label('Preparer contact number')->maxLength(150),
                TextInput::make('details.prepared_email')->label('Preparer email address')->email()->maxLength(150),
                Textarea::make('details.letterhead_text')->label('HEI letterhead text (optional)')->maxLength(1000),
                Select::make('details.letterhead_document_id')->label('Clean letterhead image (optional)')->searchable()
                    ->options(fn (Get $get) => self::documents($institutionId, $get)->whereIn('mime', ['image/png', 'image/jpeg'])->where('size', '<=', 2 * 1024 * 1024)->pluck('original_name', 'id')->all()),
            ])->description('Print/sign externally, then upload a clean signed Change report PDF. Immediate notice does not need print signatories.')->columns(2)->columnSpanFull(),
        ];
    }

    private static function documents(?int $institutionId, Get $get): Builder
    {
        return Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $institutionId ?? ($get('institution_id') ? (int) $get('institution_id') : null))))
            ->where('scan_status', 'clean')->where('confidentiality', 'institutional');
    }
}
