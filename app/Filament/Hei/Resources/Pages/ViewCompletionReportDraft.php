<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\CompletionReportDraftResource;
use App\Filament\Support\WorkflowUi;
use Filament\Resources\Pages\ViewRecord;

class ViewCompletionReportDraft extends ViewRecord
{
    protected static string $resource = CompletionReportDraftResource::class;

    protected function getHeaderActions(): array
    {
        return array_map(fn ($action) => $action->record($this->getRecord()), WorkflowUi::actions(static::getResource()::getModel()));
    }
}
