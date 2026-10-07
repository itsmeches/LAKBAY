<?php

namespace App\Filament\Support;

use App\Models\Document;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class ItineraryForms
{
    public static function activitySection(): Section
    {
        $signatories = [];
        foreach (['prepared_by' => 'Prepared by', 'reviewed_by' => 'Reviewed by', 'noted_by' => 'Noted by'] as $role => $label) {
            foreach (['name' => 'Printed name', 'position' => 'Designation / position'] as $field => $fieldLabel) {
                $signatories[] = TextInput::make('annex_j.signatories.'.$role.'.'.$field)->label($label.' - '.$fieldLabel)->maxLength(150);
            }
            if ($role === 'prepared_by') {
                $signatories[] = TextInput::make('annex_j.signatories.prepared_by.contact')->label('Preparer contact number')->maxLength(150);
                $signatories[] = TextInput::make('annex_j.signatories.prepared_by.email')->label('Preparer email address')->email()->maxLength(255);
            }
        }

        return Section::make('Detailed itinerary (Annex J)')->description('Basic information comes from the HEI profile and activity plan. Enter itinerary dates/times in Asia/Manila; review signatories after changing the plan. Printing does not create signed evidence or send a submission.')
            ->schema([
                Repeater::make('itinerary')->label('II. Detailed itinerary')->schema([
                    DateTimePicker::make('starts_at')->label('Date and time (Asia/Manila)')->required(),
                    Textarea::make('description')->label('Activity')->required()->maxLength(1000),
                    TextInput::make('location')->label('Venue / location')->required()->maxLength(255),
                    Textarea::make('dot_accreditation')->label('Information on DOT accreditation (if applicable)')->maxLength(255)
                        ->helperText('Record the applicable reference/information; a value does not verify accreditation. Leave blank where not applicable.'),
                    TextInput::make('responsible_person')->label('Person/s responsible')->required()->maxLength(255),
                    TextInput::make('locality')->label('Locality (optional operational reference)')->maxLength(255),
                ])->defaultItems(0)->maxItems(200)->columns(2)->reorderable()->columnSpanFull(),
                Section::make('Prepared, reviewed and noted by')->schema([
                    ...$signatories,
                    Textarea::make('annex_j.letterhead_text')->label('HEI letterhead text (optional)')->maxLength(1000),
                    Select::make('annex_j.letterhead_document_id')->label('Letterhead image (optional)')->searchable()
                        ->options(fn (Get $get) => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $get('institution_id') ? (int) $get('institution_id') : null)))
                            ->where('confidentiality', 'institutional')->whereIn('mime', ['image/png', 'image/jpeg'])->where('size', '<=', 2 * 1024 * 1024)->pluck('original_name', 'id')->all())
                        ->helperText('Choose a clean private institutional PNG/JPG of up to 2 MB.'),
                ])->columns(2)->columnSpanFull(),
            ])->collapsible()->collapsed()->columnSpanFull();
    }
}
