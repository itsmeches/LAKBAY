<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\CaseRecord;
use App\Services\CaseProcedure;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CaseRecordResource extends ScopedResource
{
    protected static ?string $model = CaseRecord::class;

    protected static ?string $createPermission = 'cases.sensitive';

    protected static string $readPermission = 'cases.sensitive';

    public static function infolist(Schema $schema): Schema
    {
        $schema = parent::infolist($schema);

        return $schema->components([...$schema->getComponents(), Section::make('Restricted procedural history')
            ->description('CMO No.24 §34, PDF p.16. Entries record external evidence; a complaint does not impose a restriction.')
            ->schema([TextEntry::make('procedure_history')->hiddenLabel()->state(fn (CaseRecord $record): string => app(CaseProcedure::class)->history($record))->extraAttributes(['style' => 'white-space:pre-wrap'])])->columnSpanFull()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCaseRecord::route('/'), 'view' => Pages\ViewCaseRecord::route('/{record}')];
    }
}
