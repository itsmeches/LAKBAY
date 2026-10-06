<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\Institution;

class InstitutionResource extends ScopedResource
{
    protected static ?string $model = Institution::class;

    protected static ?string $createPermission = 'institutions.manage';

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListInstitution::route('/'), 'view' => Pages\ViewInstitution::route('/{record}')];
    }
}
