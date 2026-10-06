<?php

namespace App\Filament\Hei\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\IncidentReport;

class IncidentReportResource extends ScopedResource
{
    protected static ?string $model = IncidentReport::class;

    protected static ?string $createPermission = 'reports.submit';

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListIncidentReport::route('/'), 'view' => Pages\ViewIncidentReport::route('/{record}')];
    }
}
