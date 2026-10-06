<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Filament\Hei\Resources\SubmissionResource;
use App\Filament\Support\Definitions;
use App\Models\Submission;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListSubmission extends ManageRecords
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => Definitions::create(Submission::class, $data))];
    }
}
