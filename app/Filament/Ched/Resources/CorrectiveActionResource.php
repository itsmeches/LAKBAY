<?php

namespace App\Filament\Ched\Resources;

class CorrectiveActionResource extends \App\Filament\Support\CorrectiveActionResource
{
    public static function getPages(): array
    {
        return ['index' => Pages\ListCorrectiveAction::route('/'), 'view' => Pages\ViewCorrectiveAction::route('/{record}')];
    }
}
