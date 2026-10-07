<?php

namespace App\Filament\Hei\Resources\Pages;

use App\Actions\Activities\SaveActivity;
use App\Filament\Hei\Resources\ActivityResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $v = $this->record->latestVersion;
        $data['starts_at'] = $v->starts_at->timezone(config('lakbay.timezone'))->format('Y-m-d H:i:s');
        $data['ends_at'] = $v->ends_at->timezone(config('lakbay.timezone'))->format('Y-m-d H:i:s');

        return array_merge($v->only(['category', 'academic_nature', 'activity_type', 'initiator', 'title', 'description', 'learning_objectives', 'program', 'responsible_office', 'starts_at', 'ends_at', 'venue', 'student_count', 'other_count', 'conditions', 'authorization', 'contracts', 'preparation', 'cloca', 'diagnostic', 'racp', 'annex_j']), $data, ['personnel' => $v->personnel->toArray(), 'itinerary' => $v->itineraryItems->sortBy('sequence')->map(fn ($item) => [
            'starts_at' => $item->starts_at->timezone(config('lakbay.timezone'))->format('Y-m-d H:i:s'),
        ] + $item->only(['description', 'location', 'locality', 'dot_accreditation', 'responsible_person']))->values()->all()]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        foreach (['institution_id', 'academic_period_id', 'calendar_id', 'campus_id'] as $key) {
            if (! empty($data[$key])) {
                $data[$key] = (int) $data[$key];
            }
        }

        return app(SaveActivity::class)->execute(auth()->user(), $data, $record);
    }
}
