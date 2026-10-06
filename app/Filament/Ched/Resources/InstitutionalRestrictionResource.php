<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\InstitutionalRestriction;

class InstitutionalRestrictionResource extends ScopedResource
{
    protected static ?string $model = InstitutionalRestriction::class;

    protected static ?string $createPermission = 'cases.sensitive';

    protected static string $readPermission = 'cases.sensitive';

    public static function getPages(): array
    {
        return ['index' => Pages\ListInstitutionalRestriction::route('/'), 'view' => Pages\ViewInstitutionalRestriction::route('/{record}')];
    }
}
