<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\DocumentResource;
use App\Filament\Support\Definitions;
use App\Models\Document;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListDocument extends ManageRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(Document::class, $data))];
    }
}
