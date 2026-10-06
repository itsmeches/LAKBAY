<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\Activity;

class ActivityResource extends ScopedResource
{
    protected static ?string $model = Activity::class;

    protected static ?string $createPermission = null;

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListActivity::route('/'), 'view' => Pages\ViewActivity::route('/{record}')];
    }
}
