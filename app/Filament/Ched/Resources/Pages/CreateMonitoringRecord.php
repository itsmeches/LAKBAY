<?php

namespace App\Filament\Ched\Resources\Pages;

use App\Filament\Ched\Resources\MonitoringRecordResource;
use App\Filament\Support\Definitions;
use App\Models\MonitoringRecord;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMonitoringRecord extends CreateRecord
{
    protected static string $resource = MonitoringRecordResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return Definitions::create(MonitoringRecord::class, $data);
    }
}
