<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\ActivityResource;
use App\Filament\Support\Definitions;
use App\Models\Activity;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListActivity extends ManageRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(Activity::class, $data))];
    }
}
