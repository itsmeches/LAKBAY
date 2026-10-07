<?php

namespace App\Filament\Support;

use App\Models\Document;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class RacpForms
{
    public static function activitySection(): Section
    {
        $risks = [];
        foreach (config('racp.areas') as $code => $label) {
            $risks[] = Select::make('racp.risks.'.$code.'.level')->label($label)->options(config('racp.risk_levels'))->placeholder('Not assessed');
            $risks[] = Textarea::make('racp.risks.'.$code.'.measure')->label('Contingency measure')->rows(3)->maxLength(1000);
        }
        $risks[] = Repeater::make('racp.other_risks')->label('Other risks (please specify)')->schema([
            TextInput::make('area')->label('Risk area')->required()->maxLength(300),
            Select::make('level')->label('Risk level')->options(config('racp.risk_levels')),
            Textarea::make('measure')->label('Contingency measure')->maxLength(1000),
        ])->defaultItems(0)->maxItems(10)->columnSpanFull()->helperText('Optional additional risks. Leave empty when none are identified; explain applicability in the relevant contingency measure.');
        $contacts = [];
        foreach (config('racp.offices') as $code => $label) {
            $contacts[] = TextInput::make('racp.contacts.'.$code.'.person')->label($label.' - Contact person')->maxLength(150);
            $contacts[] = Textarea::make('racp.contacts.'.$code.'.details')->label($label.' - Contact details')->rows(2)->maxLength(500);
        }
        $contacts[] = Repeater::make('racp.other_contacts')->label('Other agencies/offices (please specify)')->schema([
            TextInput::make('office')->label('Agency/office')->required()->maxLength(150),
            TextInput::make('person')->label('Contact person')->maxLength(150),
            Textarea::make('details')->label('Contact details')->maxLength(500),
        ])->defaultItems(0)->maxItems(10)->columnSpanFull();
        $signatories = [];
        foreach (['prepared_by' => 'Prepared by', 'reviewed_by' => 'Reviewed by', 'noted_by' => 'Noted by'] as $role => $label) {
            foreach (['name' => 'Printed name', 'position' => 'Designation / position'] as $field => $fieldLabel) {
                $signatories[] = TextInput::make('racp.signatories.'.$role.'.'.$field)->label($label.' - '.$fieldLabel)->maxLength(150);
            }
            if ($role === 'prepared_by') {
                $signatories[] = TextInput::make('racp.signatories.prepared_by.contact')->label('Preparer contact number')->maxLength(150);
                $signatories[] = TextInput::make('racp.signatories.prepared_by.email')->label('Preparer email address')->email()->maxLength(255);
            }
        }

        return Section::make('Risk assessment and contingency plan (Annex E)')->description('Optional preparation form for this activity. Basic information comes from the activity plan. Enter activity-level measures, not individual student diagnoses or medical histories. Risk ratings and signatures are not verified by generating a PDF.')
            ->schema([
                Section::make('II. Risk assessment')->schema($risks)->columns(2)->collapsible()->columnSpanFull(),
                Section::make('III. Emergency contact information')->schema($contacts)->columns(2)->collapsible()->columnSpanFull(),
                Section::make('IV. Certification and signatories')->description(config('racp.certification'))->schema([
                    DatePicker::make('racp.issued_on')->label('Issued on (optional)')->helperText('Leave blank for signing if unknown. Entering a date does not certify completion or prove execution.'),
                    ...$signatories,
                    Textarea::make('racp.letterhead_text')->label('HEI letterhead text (optional)')->maxLength(1000),
                    Select::make('racp.letterhead_document_id')->label('Letterhead image (optional)')->searchable()
                        ->options(fn (Get $get) => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $get('institution_id') ? (int) $get('institution_id') : null)))
                            ->where('confidentiality', 'institutional')->whereIn('mime', ['image/png', 'image/jpeg'])->where('size', '<=', 2 * 1024 * 1024)->pluck('original_name', 'id')->all())
                        ->helperText('Choose a clean private institutional PNG/JPG of up to 2 MB.'),
                ])->columns(2)->columnSpanFull(),
            ])->collapsible()->collapsed()->columnSpanFull();
    }
}
