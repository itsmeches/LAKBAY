<?php

namespace App\Filament\Hei\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\ChangeReportDraft;

class ChangeReportDraftResource extends ScopedResource
{
    protected static ?string $model = ChangeReportDraft::class;

    protected static ?string $createPermission = 'reports.submit';

    public static function getPages(): array
    {
        return ['index' => Pages\ListChangeReportDraft::route('/'), 'view' => Pages\ViewChangeReportDraft::route('/{record}')];
    }
}
