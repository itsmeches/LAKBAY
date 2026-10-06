<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\TechnicalAssistanceLog;

class TechnicalAssistanceLogResource extends ScopedResource
{
    protected static ?string $model = TechnicalAssistanceLog::class;

    protected static ?string $createPermission = 'monitoring.manage';

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListTechnicalAssistanceLog::route('/'), 'view' => Pages\ViewTechnicalAssistanceLog::route('/{record}')];
    }
}
