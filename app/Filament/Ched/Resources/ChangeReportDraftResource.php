<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\ChangeReportDraft;

class ChangeReportDraftResource extends ScopedResource
{
    protected static ?string $model = ChangeReportDraft::class;

    protected static ?string $createPermission = null;

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListChangeReportDraft::route('/'), 'view' => Pages\ViewChangeReportDraft::route('/{record}')];
    }
}
