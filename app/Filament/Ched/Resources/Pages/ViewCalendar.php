<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\CalendarResource;
use App\Filament\Support\WorkflowUi;
use Filament\Resources\Pages\ViewRecord;

class ViewCalendar extends ViewRecord
{
    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return array_map(
            fn ($action) => $action->record($this->getRecord()),
            WorkflowUi::actions(static::getResource()::getModel()),
        );
    }
}
