<?php

namespace App\Actions\Reports;

use App\Models\ActivityVersion;
use App\Models\CompletionReportDraft;
use App\Models\Document;
use App\Models\IncidentReport;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\CompletionReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveCompletionReportDraft
{
    public function execute(User $actor, array $data, ?CompletionReportDraft $draft = null): CompletionReportDraft
    {
        Validator::make($data, ['title' => 'required|string|max:255',
            'details' => 'required|array:ended_at,venue,student_count,other_count,implementation,summary,change_explanation,incidents_encountered,incident_explanation,incident_report_ids,official,position,issued_on,letterhead_text,letterhead_document_id',
            'details.ended_at' => 'nullable|date', 'details.venue' => 'nullable|string|max:1000',
            'details.student_count' => 'nullable|integer|min:0|max:1000000', 'details.other_count' => 'nullable|integer|min:0|max:1000000',
            'details.implementation' => 'nullable|in:as_authorized,with_changes',
            'details.summary' => 'nullable|string|max:6000', 'details.change_explanation' => 'nullable|string|max:3000',
            'details.incidents_encountered' => 'required|boolean', 'details.incident_explanation' => 'nullable|string|max:3000',
            'details.incident_report_ids' => 'nullable|array|max:100', 'details.incident_report_ids.*' => 'integer|distinct',
            'details.official' => 'nullable|string|max:150', 'details.position' => 'nullable|string|max:150',
            'details.issued_on' => 'nullable|date', 'details.letterhead_text' => 'nullable|string|max:1000',
            'details.letterhead_document_id' => 'nullable|integer'])->validate();
        if (! $draft) {
            Validator::make($data, ['institution_id' => 'required|integer', 'activity_version_id' => 'required|integer'])->validate();
        }
        $version = ActivityVersion::findOrFail($draft?->activity_version_id ?? $data['activity_version_id']);
        $institutionId = $version->activity->institution_id;
        $access = app(Access::class);
        $access->authorize($actor, $institutionId, 'reports.submit');
        abort_unless($actor->can('panel.hei') && $access->owns($actor, $institutionId), 403);
        abort_if(isset($data['institution_id']) && (int) $data['institution_id'] !== $institutionId, 403);
        abort_if($draft && isset($data['activity_version_id']) && (int) $data['activity_version_id'] !== $draft->activity_version_id, 422, 'Create a new draft to report a different activity version.');
        $details = $data['details'];
        if (filled($details['ended_at'] ?? null)) {
            $ended = CarbonImmutable::parse($details['ended_at'], config('lakbay.timezone'))->utc();
            if ($ended->isFuture() || ($version->activity->actual_ended_at && ! $version->activity->actual_ended_at->equalTo($ended))) {
                throw ValidationException::withMessages(['details.ended_at' => 'Use the recorded actual conclusion, which cannot be in the future.']);
            }
        }
        if (! $details['incidents_encountered'] && (filled($details['incident_explanation'] ?? null) || ! empty($details['incident_report_ids']))) {
            throw ValidationException::withMessages(['details.incidents_encountered' => 'Declare incidents or clear the incident explanation and links.']);
        }
        foreach ($details['incident_report_ids'] ?? [] as $id) {
            abort_unless(IncidentReport::findOrFail($id)->activity_id === $version->activity_id, 403);
        }
        if ($id = $details['letterhead_document_id'] ?? null) {
            $image = Document::findOrFail($id);
            abort_unless($image->institution_id === $institutionId && $access->document($actor, $image), 403);
            abort_unless($image->scan_status === 'clean' && $image->confidentiality === 'institutional'
                && in_array($image->mime, ['image/png', 'image/jpeg'], true) && $image->size <= 2 * 1024 * 1024, 422, 'Choose a clean institutional PNG/JPG letterhead up to 2 MB.');
        }

        return DB::transaction(function () use ($actor, $data, $details, $draft, $version, $institutionId): CompletionReportDraft {
            $values = ['title' => $data['title'], 'details' => $details, 'updated_by' => $actor->id];
            if ($draft) {
                $record = CompletionReportDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
                if ($record->completionReport()->exists()) {
                    throw ValidationException::withMessages(['title' => 'This draft has been filed; its preparation record is locked.']);
                }
                $record->update($values + ['revision' => $record->revision + 1]);
            } else {
                $record = CompletionReportDraft::create($values + ['institution_id' => $institutionId, 'activity_id' => $version->activity_id,
                    'activity_version_id' => $version->id, 'context' => app(CompletionReportBuilder::class)->context($version), 'created_by' => $actor->id]);
            }
            Audit::record($actor, $draft ? 'completion.draft_revised' : 'completion.draft_created', $record);

            return $record;
        });
    }
}
