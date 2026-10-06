<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\MonitoringRecordResource;
use App\Filament\Support\Definitions;
use App\Models\MonitoringRecord;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListMonitoringRecord extends ManageRecords
{
    protected static string $resource = MonitoringRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(MonitoringRecord::class, $data))];
    }
}
