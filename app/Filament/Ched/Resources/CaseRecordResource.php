<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\CaseRecord;

class CaseRecordResource extends ScopedResource
{
    protected static ?string $model = CaseRecord::class;

    protected static ?string $createPermission = 'cases.sensitive';

    protected static string $readPermission = 'cases.sensitive';

    public static function getPages(): array
    {
        return ['index' => Pages\ListCaseRecord::route('/'), 'view' => Pages\ViewCaseRecord::route('/{record}')];
    }
}
