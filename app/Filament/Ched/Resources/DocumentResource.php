<?php

namespace App\Filament\Ched\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\Document;

class DocumentResource extends ScopedResource
{
    protected static ?string $model = Document::class;

    protected static ?string $createPermission = 'documents.upload';

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListDocument::route('/'), 'view' => Pages\ViewDocument::route('/{record}')];
    }
}
