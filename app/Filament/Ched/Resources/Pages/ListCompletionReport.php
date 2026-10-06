<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\CompletionReportResource;
use App\Filament\Support\Definitions;
use App\Models\CompletionReport;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListCompletionReport extends ManageRecords
{
    protected static string $resource = CompletionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(CompletionReport::class, $data))];
    }
}
