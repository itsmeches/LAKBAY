<?php

namespace App\Filament\Hei\Resources;

use App\Filament\Support\ScopedResource;
use App\Models\Submission;

class SubmissionResource extends ScopedResource
{
    protected static ?string $model = Submission::class;

    protected static ?string $createPermission = null;

    protected static string $readPermission = 'records.view';

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubmission::route('/'), 'view' => Pages\ViewSubmission::route('/{record}')];
    }
}
