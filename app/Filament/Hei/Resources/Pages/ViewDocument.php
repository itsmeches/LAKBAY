<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\DocumentResource;
use App\Filament\Support\WorkflowUi;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return array_map(
            fn ($action) => $action->record($this->getRecord()),
            WorkflowUi::actions(static::getResource()::getModel()),
        );
    }
}
