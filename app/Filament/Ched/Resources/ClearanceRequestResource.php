<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\ClearanceRequest;

class ClearanceRequestResource extends ScopedResource
{
    protected static ?string $model = ClearanceRequest::class;

    protected static ?string $createPermission = 'cases.sensitive';

    protected static string $readPermission = 'cases.sensitive';

    public static function getPages(): array
    {
        return ['index' => Pages\ListClearanceRequest::route('/'), 'view' => Pages\ViewClearanceRequest::route('/{record}')];
    }
}
