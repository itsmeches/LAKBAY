<?php

namespace App\Filament\Hei\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\Activity;
use App\Services\Access;
use Illuminate\Database\Eloquent\Model;

class ActivityResource extends ScopedResource
{
    protected static ?string $model = Activity::class;

    protected static ?string $createPermission = 'activities.manage';

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListActivity::route('/'), 'view' => Pages\ViewActivity::route('/{record}'), 'edit' => Pages\EditActivity::route('/{record}/revise')];
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('activities.manage') && app(Access::class)->owns(auth()->user(), $record->institution_id);
    }
}
