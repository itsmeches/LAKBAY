<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\CalendarResource;
use App\Filament\Support\Definitions;
use App\Models\Calendar;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListCalendar extends ManageRecords
{
    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(Calendar::class, $data))];
    }
}
