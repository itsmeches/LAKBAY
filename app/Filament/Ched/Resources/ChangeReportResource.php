<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\ChangeReport;

class ChangeReportResource extends ScopedResource
{
    protected static ?string $model = ChangeReport::class;

    protected static ?string $createPermission = null;

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListChangeReport::route('/'), 'view' => Pages\ViewChangeReport::route('/{record}')];
    }
}
