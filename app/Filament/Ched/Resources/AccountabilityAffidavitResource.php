<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\AccountabilityAffidavit;

class AccountabilityAffidavitResource extends ScopedResource
{
    protected static ?string $model = AccountabilityAffidavit::class;

    protected static ?string $createPermission = null;

    public static function getPages(): array
    {
        return ['index' => Pages\ListAccountabilityAffidavit::route('/'), 'view' => Pages\ViewAccountabilityAffidavit::route('/{record}')];
    }
}
