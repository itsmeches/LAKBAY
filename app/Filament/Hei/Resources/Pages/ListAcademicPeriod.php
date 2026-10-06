<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\AcademicPeriodResource;
use App\Filament\Support\Definitions;
use App\Models\AcademicPeriod;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListAcademicPeriod extends ManageRecords
{
    protected static string $resource = AcademicPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(AcademicPeriod::class, $data))];
    }
}
