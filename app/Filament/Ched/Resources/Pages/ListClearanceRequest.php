<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\ClearanceRequestResource;
use App\Filament\Support\Definitions;
use App\Models\ClearanceRequest;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListClearanceRequest extends ManageRecords
{
    protected static string $resource = ClearanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(ClearanceRequest::class, $data))];
    }
}
