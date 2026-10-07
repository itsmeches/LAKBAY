<?php

namespace App\Filament\Hei\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\IncidentReportDraft;

class IncidentReportDraftResource extends ScopedResource
{
    protected static ?string $model = IncidentReportDraft::class;

    protected static ?string $createPermission = 'reports.submit';

    public static function getPages(): array
    {
        return ['index' => Pages\ListIncidentReportDraft::route('/'), 'view' => Pages\ViewIncidentReportDraft::route('/{record}')];
    }
}
