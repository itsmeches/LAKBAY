<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\IncidentReportDraftResource;
use App\Filament\Support\Definitions;
use App\Models\IncidentReportDraft;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListIncidentReportDraft extends ManageRecords
{
    protected static string $resource = IncidentReportDraftResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New incident draft')->using(fn (array $data) => Definitions::create(IncidentReportDraft::class, $data))];
    }
}
