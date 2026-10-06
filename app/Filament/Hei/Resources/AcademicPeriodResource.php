<?php

namespace App\Filament\Hei\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\AcademicPeriod;

class AcademicPeriodResource extends ScopedResource
{
    protected static ?string $model = AcademicPeriod::class;

    protected static ?string $createPermission = 'activities.manage';

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListAcademicPeriod::route('/'), 'view' => Pages\ViewAcademicPeriod::route('/{record}')];
    }
}
