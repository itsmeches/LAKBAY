<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\IncidentReportDraftResource;
use App\Filament\Support\WorkflowUi;
use Filament\Resources\Pages\ViewRecord;

class ViewIncidentReportDraft extends ViewRecord
{
    protected static string $resource = IncidentReportDraftResource::class;

    protected function getHeaderActions(): array
    {
        return array_map(fn ($action) => $action->record($this->getRecord()), WorkflowUi::actions(static::getResource()::getModel()));
    }
}
