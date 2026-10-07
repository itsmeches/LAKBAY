<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\AccountabilityAffidavitResource;
use App\Filament\Support\Definitions;
use App\Models\AccountabilityAffidavit;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListAccountabilityAffidavit extends ManageRecords
{
    protected static string $resource = AccountabilityAffidavitResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New SAA draft')->using(fn (array $data) => Definitions::create(AccountabilityAffidavit::class, $data))];
    }
}
