<?php

namespace App\Filament\Hei\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\CertificationDraft;

class CertificationDraftResource extends ScopedResource
{
    protected static ?string $model = CertificationDraft::class;

    protected static ?string $createPermission = 'activities.manage';

    public static function getPages(): array
    {
        return ['index' => Pages\ListCertificationDraft::route('/'), 'view' => Pages\ViewCertificationDraft::route('/{record}')];
    }
}
