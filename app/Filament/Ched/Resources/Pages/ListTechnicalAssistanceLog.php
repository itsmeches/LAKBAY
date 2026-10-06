<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\TechnicalAssistanceLogResource;
use App\Filament\Support\Definitions;
use App\Models\TechnicalAssistanceLog;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListTechnicalAssistanceLog extends ManageRecords
{
    protected static string $resource = TechnicalAssistanceLogResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(TechnicalAssistanceLog::class, $data))];
    }
}
