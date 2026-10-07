<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\CompletionReportDraft;

class CompletionReportDraftResource extends ScopedResource
{
    protected static ?string $model = CompletionReportDraft::class;

    protected static ?string $createPermission = null;

    public static function getPages(): array
    {
        return ['index' => Pages\ListCompletionReportDraft::route('/'), 'view' => Pages\ViewCompletionReportDraft::route('/{record}')];
    }
}
