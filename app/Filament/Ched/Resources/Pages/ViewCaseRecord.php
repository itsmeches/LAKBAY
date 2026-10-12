<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\CaseRecordResource;
use App\Filament\Support\WorkflowUi;
use App\Models\CaseRecord;
use Filament\Resources\Pages\ViewRecord;

class ViewCaseRecord extends ViewRecord
{
    protected static string $resource = CaseRecordResource::class;

    protected function getHeaderActions(): array
    {
        return WorkflowUi::actions(CaseRecord::class);
    }
}
