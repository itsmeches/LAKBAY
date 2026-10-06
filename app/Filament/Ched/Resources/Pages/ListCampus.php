<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\CampusResource;
use App\Filament\Support\Definitions;
use App\Models\Campus;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListCampus extends ManageRecords
{
    protected static string $resource = CampusResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(Campus::class, $data))];
    }
}
