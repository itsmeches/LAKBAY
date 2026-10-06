<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\MonitoringRecord;

class MonitoringRecordResource extends ScopedResource
{
    protected static ?string $model = MonitoringRecord::class;

    protected static ?string $createPermission = 'monitoring.manage';

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListMonitoringRecord::route('/'), 'view' => Pages\ViewMonitoringRecord::route('/{record}')];
    }
}
