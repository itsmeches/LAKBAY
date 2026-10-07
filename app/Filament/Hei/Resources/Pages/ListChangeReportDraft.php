<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\ChangeReportDraftResource;
use App\Filament\Support\Definitions;
use App\Models\ChangeReportDraft;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListChangeReportDraft extends ManageRecords
{
    protected static string $resource = ChangeReportDraftResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New change draft')->using(fn (array $data) => Definitions::create(ChangeReportDraft::class, $data))];
    }
}
