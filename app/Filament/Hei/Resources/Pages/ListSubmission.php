<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\SubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListSubmission extends ListRecords
{
    protected static string $resource = SubmissionResource::class;
}
