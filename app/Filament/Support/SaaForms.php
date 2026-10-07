<?php

namespace App\Filament\Support;

use App\Models\Document;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class SaaForms
{
    public static function details(?int $institutionId = null): array
    {
        $affiant = [TextInput::make('title')->required()->maxLength(255)];
        foreach (['name' => 'Affiant / HEI head complete name', 'position' => 'Affiant position',
            'citizenship' => 'Citizenship', 'semester' => 'Semester covered', 'academic_year' => 'Academic year'] as $key => $label) {
            $field = TextInput::make('details.'.$key)->label($label)->maxLength(150);
            if ($key === 'citizenship') {
                $field->default('Filipino')->helperText('Confirm the affiant information; do not infer it from the account.');
            }
            if (in_array($key, ['semester', 'academic_year'], true)) {
                $field->helperText('Defaults to the academic period. Confirm the semester coverage of this affidavit.');
            }
            $affiant[] = $field;
        }
        $affiant[] = Textarea::make('details.office_address')->label('Affiant office address')->maxLength(300)->helperText('Defaults to the HEI profile address.');
        $affiant[] = Textarea::make('details.letterhead_text')->label('HEI letterhead text (optional)')->maxLength(1000);
        $affiant[] = Select::make('details.letterhead_document_id')->label('Letterhead image (optional)')->searchable()
            ->options(fn (Get $get) => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $institutionId ?? ($get('institution_id') ? (int) $get('institution_id') : null))))
                ->where('confidentiality', 'institutional')->whereIn('mime', ['image/png', 'image/jpeg'])->where('size', '<=', 2 * 1024 * 1024)->pluck('original_name', 'id')->all())
            ->helperText('Choose a clean institutional PNG/JPG up to 2 MB from the Document library.');
        $notarial = [DatePicker::make('details.notarial_date')->label('Notarial date (optional)')];
        foreach (['notarial_place' => 'Notarial place', 'notary_name' => 'Notary public name',
            'identity_description' => 'Competent evidence of identity - description only',
            'doc_number' => 'Doc. No.', 'page_number' => 'Page No.', 'book_number' => 'Book No.', 'series' => 'Series'] as $key => $label) {
            $notarial[] = TextInput::make('details.'.$key)->label($label.' (optional)')->maxLength(150);
        }

        return [
            Section::make('Affiant and semester coverage')->description('Annex C identifies the signature of the HEAD OF HEI. Do not substitute the logged-in preparer automatically.')->schema($affiant)->columns(2)->columnSpanFull(),
            Section::make('Execution details')->description('Leave blank for completion at signing if unknown. Entering a date is not proof of execution.')
                ->schema([DatePicker::make('details.execution_date')->label('Signing date (optional)'), TextInput::make('details.execution_place')->label('Signing place (optional)')->maxLength(150)])->columns(2)->columnSpanFull(),
            Section::make('Notarial particulars')->description('Optional preparation fields. Keep government ID numbers and copies on the signed original; do not enter them here. Generation never records notarization.')
                ->schema($notarial)->columns(2)->collapsible()->collapsed()->columnSpanFull(),
        ];
    }
}
