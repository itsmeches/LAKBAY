<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\MonitoringRecord;
use Illuminate\Auth\Access\Response;

class MonitoringRecordResource extends ScopedResource
{
    protected static ?string $model = MonitoringRecord::class;

    protected static ?string $createPermission = 'monitoring.manage';

    protected static string $readPermission = 'records.view';

    public static function getCreateAuthorizationResponse(): Response
    {
        $user = auth()->user();

        return $user?->is_active && $user->hasVerifiedEmail() && $user->region_id
            && $user->can('panel.ched') && $user->can('monitoring.manage')
            ? Response::allow() : Response::deny();
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMonitoringRecord::route('/'),
            'create' => Pages\CreateMonitoringRecord::route('/create'),
            'view' => Pages\ViewMonitoringRecord::route('/{record}')];
    }
}
