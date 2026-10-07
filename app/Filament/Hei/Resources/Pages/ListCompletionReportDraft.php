<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\CompletionReportDraftResource;
use App\Filament\Support\Definitions;
use App\Models\CompletionReportDraft;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListCompletionReportDraft extends ManageRecords
{
    protected static string $resource = CompletionReportDraftResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New ROC draft')->using(fn (array $data) => Definitions::create(CompletionReportDraft::class, $data))];
    }
}
