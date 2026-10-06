<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\InstitutionalRestrictionResource;
use App\Filament\Support\Definitions;
use App\Models\InstitutionalRestriction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListInstitutionalRestriction extends ManageRecords
{
    protected static string $resource = InstitutionalRestrictionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(InstitutionalRestriction::class, $data))];
    }
}
