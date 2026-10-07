<?php

namespace App\Actions\Activities;

use App\Models\AcademicPeriod;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaveClocaCalendar
{
    public function execute(User $actor, array $data, ?Calendar $calendar = null): Calendar
    {
        $institutionId = $calendar?->institution_id ?? (int) ($data['institution_id'] ?? 0);
        app(Access::class)->authorize($actor, $institutionId, 'activities.manage');
        Validator::make($data, [
            'title' => 'required|string|max:255',
            'effective_on' => 'nullable|date',
            'responsible_office' => 'required|string|max:255',
            'annex_b' => 'nullable|array:address,academic_year,semester,letterhead_text,letterhead_document_id,prepared_by,reviewed_by,noted_by',
            'annex_b.address' => 'nullable|string|max:1000',
            'annex_b.academic_year' => 'nullable|string|max:100',
            'annex_b.semester' => 'nullable|string|max:100',
            'annex_b.letterhead_text' => 'nullable|string|max:1000',
            'annex_b.letterhead_document_id' => 'nullable|integer',
            'annex_b.prepared_by' => 'nullable|array:name,position',
            'annex_b.reviewed_by' => 'nullable|array:name,position',
            'annex_b.noted_by' => 'nullable|array:name,position',
            'annex_b.*.name' => 'nullable|string|max:255',
            'annex_b.*.position' => 'nullable|string|max:255',
        ])->validate();
        if (! $calendar) {
            Validator::make($data, ['academic_period_id' => 'required|integer|exists:academic_periods,id'])->validate();
            abort_unless(AcademicPeriod::findOrFail($data['academic_period_id'])->institution_id === $institutionId, 403);
        }
        if ($documentId = data_get($data, 'annex_b.letterhead_document_id')) {
            $document = Document::findOrFail($documentId);
            abort_unless($document->institution_id === $institutionId && app(Access::class)->document($actor, $document), 403);
            abort_unless($document->scan_status === 'clean' && $document->confidentiality === 'institutional'
                && in_array($document->mime, ['image/png', 'image/jpeg'], true) && $document->size <= 2 * 1024 * 1024, 422, 'Choose a clean institutional PNG/JPG letterhead of up to 2 MB.');
        }

        return DB::transaction(function () use ($actor, $data, $calendar, $institutionId): Calendar {
            $values = collect($data)->only(['title', 'effective_on', 'responsible_office', 'annex_b'])->all();
            if ($calendar) {
                $record = Calendar::whereKey($calendar->id)->lockForUpdate()->firstOrFail();
                $record->update($values);
            } else {
                $record = Calendar::create($values + ['institution_id' => $institutionId,
                    'academic_period_id' => (int) $data['academic_period_id'], 'created_by' => $actor->id]);
            }
            Audit::record($actor, $calendar ? 'calendar.print_details_revised' : 'reference.created', $record);

            return $record;
        });
    }
}
