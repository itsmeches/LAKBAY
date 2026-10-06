<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\IncidentReportResource;
use App\Filament\Support\Definitions;
use App\Models\IncidentReport;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListIncidentReport extends ManageRecords
{
    protected static string $resource = IncidentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(IncidentReport::class, $data))];
    }
}
