<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\ChangeReportResource;
use App\Filament\Support\Definitions;
use App\Models\ChangeReport;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListChangeReport extends ManageRecords
{
    protected static string $resource = ChangeReportResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(ChangeReport::class, $data))];
    }
}
