<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\CaseRecordResource;
use App\Filament\Support\Definitions;
use App\Models\CaseRecord;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListCaseRecord extends ManageRecords
{
    protected static string $resource = CaseRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(CaseRecord::class, $data))];
    }
}
