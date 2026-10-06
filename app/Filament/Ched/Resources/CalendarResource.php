<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\Calendar;

class CalendarResource extends ScopedResource
{
    protected static ?string $model = Calendar::class;

    protected static ?string $createPermission = null;

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListCalendar::route('/'), 'view' => Pages\ViewCalendar::route('/{record}')];
    }
}
