<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\InstitutionResource;
use App\Filament\Support\Definitions;
use App\Models\Institution;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListInstitution extends ManageRecords
{
    protected static string $resource = InstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(Institution::class, $data))];
    }
}
