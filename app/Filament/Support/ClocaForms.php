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

class ClocaForms
{
    public const TYPE_CODES = ['I' => 'I - Institutional', 'P' => 'P - Program-based', 'C' => 'C - Curriculum-based',
        'S' => 'S - Student-initiated', 'G' => 'G - NGA / LGU invitation', 'O' => 'O - Others'];

    public static function calendarDetails(?int $institutionId = null): array
    {
        $fields = [
            TextInput::make('title')->required()->maxLength(255),
            DatePicker::make('effective_on')->label('CLOCA effectivity')->helperText('Calendar effectivity, separate from policy effectivity.'),
            TextInput::make('responsible_office')->label('HEI office / organization responsible')->required()->maxLength(255),
            Textarea::make('annex_b.address')->label('HEI address for this CLOCA')->helperText('Defaults to the institution profile address.')->maxLength(1000),
            TextInput::make('annex_b.academic_year')->label('Printed academic year')->helperText('Defaults to the selected academic period label.')->maxLength(100),
            TextInput::make('annex_b.semester')->label('Printed term / semester')->helperText('Defaults to the academic period term.')->maxLength(100),
            Textarea::make('annex_b.letterhead_text')->label('HEI letterhead text')->helperText('Optional text; defaults to HEI name and address.')->maxLength(1000),
            Select::make('annex_b.letterhead_document_id')->label('Letterhead image (optional)')->searchable()
                ->options(fn (Get $get) => Document::whereIn('id', array_keys(Fields::related(Document::class, 'original_name', $institutionId ?? ($get('institution_id') ? (int) $get('institution_id') : null))))
                    ->where('confidentiality', 'institutional')->whereIn('mime', ['image/png', 'image/jpeg'])->where('size', '<=', 2 * 1024 * 1024)->pluck('original_name', 'id')->all())
                ->helperText('Upload a PNG/JPG in the Document library first; only clean images up to 2 MB are available.'),
        ];
        foreach (['prepared_by' => 'Prepared by', 'reviewed_by' => 'Reviewed by', 'noted_by' => 'Noted by'] as $key => $label) {
            $fields[] = TextInput::make('annex_b.'.$key.'.name')->label($label.' - printed name')->maxLength(255);
            $fields[] = TextInput::make('annex_b.'.$key.'.position')->label($label.' - designation / position')->maxLength(255);
        }

        return $fields;
    }

    public static function activitySection(): Section
    {
        return Section::make('CLOCA printed row (Annex B)')->description('Reuses the activity name, description, schedule, PIC/APIC and participant counts above. These fields complete its calendar row.')
            ->schema([
                Select::make('cloca.activity_type_code')->label('CHED activity type code')->options(self::TYPE_CODES),
                Textarea::make('cloca.brief_description')->label('Brief description for CLOCA (optional)')->maxLength(600)
                    ->helperText('Defaults to the activity description. Use a short summary for the printed calendar.'),
                TextInput::make('cloca.duration_days')->label('Number of days')->integer()->minValue(1)->maxValue(3660)
                    ->helperText('Confirm the total activity duration. This is not a submission deadline.'),
                Repeater::make('cloca.locations')->label('Itinerary locations')->schema([
                    TextInput::make('name')->label('Location')->required()->maxLength(500),
                    Select::make('region_scope')->label('Relative to HEI region')->options(['within' => 'Within region', 'outside' => 'Outside region'])->required(),
                ])->defaultItems(0)->maxItems(100)->columns(2)->columnSpanFull(),
                Textarea::make('cloca.agency_coordination')->label('Coordination with PNP, LGU, etc.')->maxLength(2000)
                    ->helperText('List agencies coordinated with. Annex B permits NA for research/field studies; record the applicable interpretation rather than inferring it automatically.'),
                Textarea::make('cloca.requested_assistance')->label('Requested provision for assistance')->maxLength(2000)
                    ->helperText('Specify the agency and assistance, for example PNP - security assistance; record NA explicitly where applicable.'),
            ])->columns(2)->columnSpanFull();
    }
}
