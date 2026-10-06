<?php

namespace App\Filament\Hei\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\CompletionReport;

class CompletionReportResource extends ScopedResource
{
    protected static ?string $model = CompletionReport::class;

    protected static ?string $createPermission = 'reports.submit';

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListCompletionReport::route('/'), 'view' => Pages\ViewCompletionReport::route('/{record}')];
    }
}
