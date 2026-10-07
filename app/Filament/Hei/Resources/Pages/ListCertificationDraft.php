<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\CertificationDraftResource;
use App\Filament\Support\Definitions;
use App\Models\CertificationDraft;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListCertificationDraft extends ManageRecords
{
    protected static string $resource = CertificationDraftResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New certification draft')->using(fn (array $data) => Definitions::create(CertificationDraft::class, $data))];
    }
}
