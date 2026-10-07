<?php

namespace App\Filament\Support;

use App\Models\Document;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class CertificationForms
{
    public static function details(?int $institutionId = null, ?array $definition = null): array
    {
        $definition ??= config('certification');
        $sections = [Section::make('Certification details')->schema([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('details.official')->label('Authorized HEI official - complete name')->maxLength(150),
            TextInput::make('details.position')->label('Designation / position')->maxLength(150),
            DatePicker::make('details.issued_on')->label('Issued on (optional)'),
            TextInput::make('details.issued_at')->label('Issued at / place (optional)')->maxLength(300),
            Textarea::make('details.letterhead_text')->label('HEI letterhead text (optional)')->maxLength(1000),
            Select::make('details.letterhead_document_id')->label('Letterhead image (optional)')->searchable()
                ->options(fn (Get $get) => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $institutionId ?? ($get('institution_id') ? (int) $get('institution_id') : null))))
                    ->where('confidentiality', 'institutional')->whereIn('mime', ['image/png', 'image/jpeg'])->where('size', '<=', 2 * 1024 * 1024)->pluck('original_name', 'id')->all())
                ->helperText('Choose a clean institutional PNG/JPG up to 2 MB.'),
        ])->description('Institution name/address come from the HEI profile. Unknown issuance date/place stay blank. Entering an official does not verify signing authority.')->columns(2)->columnSpanFull()];
        foreach ($definition['rows'] as $code => $row) {
            $path = 'details.items.'.$code;
            $fields = [Select::make($path.'.status')->label('HEI attestation')->options([
                'attested' => 'Attested / complied', 'pending' => 'Pending / not yet confirmed', 'not_applicable' => 'Not applicable',
            ])->placeholder('Not assessed')->live(),
                Textarea::make($path.'.reason')->label('Applicability reason / preparation note')->maxLength(500)
                    ->required(fn (Get $get) => $get($path.'.status') === 'not_applicable')
                    ->helperText('Explain every N/A decision. N/A notes are printed separately; other preparation notes are retained internally.')];
            foreach ($row['supporting'] as $key => $label) {
                $fields[] = Toggle::make($path.'.supporting.'.$key)->label($label)->default(false)
                    ->helperText('Confirm that this supporting document is complete and available. This is not an upload or verification.');
            }
            $description = $row['requirement'];
            if (isset($row['particulars'])) {
                $description .= ' '.implode('; ', $row['particulars']).'. '.$row['note'];
            }
            $sections[] = Section::make(ucfirst(str_replace('_', ' ', $code)))->description($description)->schema($fields)->columns(2)->collapsible()->columnSpanFull();
        }

        return [Section::make('Annex F: requirements and supporting documents')->description('Complete both pages. Check only genuinely attested requirements and available supporting documents. Printing needs every row attested or explicitly N/A with a reason; pending items remain draft-only. These choices do not replace the policy checklist or signed evidence.')
            ->schema($sections)->columnSpanFull()];
    }
}
