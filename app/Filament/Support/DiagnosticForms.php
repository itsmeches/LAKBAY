<?php

namespace App\Filament\Support;

use App\Models\Document;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class DiagnosticForms
{
    public static function activitySection(): Section
    {
        $sections = [];
        foreach (config('diagnostic.groups') as $group => $criteria) {
            $fields = [];
            foreach ($criteria as $code => $question) {
                $fields[] = Select::make('diagnostic.answers.'.$code.'.answer')->label($question)
                    ->options(['yes' => 'Yes', 'no' => 'No'])->placeholder('Not answered');
                $fields[] = Textarea::make('diagnostic.answers.'.$code.'.remarks')->label('Remarks')->rows(2)->maxLength(1000);
            }
            $sections[] = Section::make($group)->schema($fields)->columns(2)->collapsible()->columnSpanFull();
        }
        $signatories = [];
        foreach (['prepared_by' => 'Prepared by', 'reviewed_by' => 'Reviewed by', 'noted_by' => 'Noted by'] as $role => $label) {
            foreach (['name' => 'Printed name', 'position' => 'Designation / position'] as $field => $fieldLabel) {
                $signatories[] = TextInput::make('diagnostic.signatories.'.$role.'.'.$field)->label($label.' - '.$fieldLabel)->maxLength(150);
            }
            if ($role === 'prepared_by') {
                $signatories[] = TextInput::make('diagnostic.signatories.prepared_by.contact')->label('Preparer contact number')->maxLength(150);
                $signatories[] = TextInput::make('diagnostic.signatories.prepared_by.email')->label('Preparer email address')->email()->maxLength(255);
            }
        }
        $sections[] = Section::make('Summary, recommendation and signatories')->schema([
            Select::make('diagnostic.recommendation')->label('HEI recommendation')->options(config('diagnostic.recommendations'))->columnSpanFull()
                ->helperText('Choose the HEI assessment. No automatic score or approval is inferred. Use remarks to explain applicability or gaps.'),
            ...$signatories,
            Textarea::make('diagnostic.letterhead_text')->label('HEI letterhead text (optional)')->maxLength(1000),
            Select::make('diagnostic.letterhead_document_id')->label('Letterhead image (optional)')->searchable()
                ->options(fn (Get $get) => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $get('institution_id') ? (int) $get('institution_id') : null)))
                    ->where('confidentiality', 'institutional')->whereIn('mime', ['image/png', 'image/jpeg'])->where('size', '<=', 2 * 1024 * 1024)->pluck('original_name', 'id')->all())
                ->helperText('Choose a clean private institutional PNG/JPG of up to 2 MB.'),
        ])->columns(2)->columnSpanFull();

        return Section::make('Diagnostic tool of HEI (Annex D)')->description('Optional preparation form for this activity. Basic information comes from the activity plan. Complete all 20 Yes/No answers and signatory details before printing; signed evidence is still uploaded separately.')
            ->schema($sections)->collapsible()->collapsed()->columnSpanFull();
    }
}
