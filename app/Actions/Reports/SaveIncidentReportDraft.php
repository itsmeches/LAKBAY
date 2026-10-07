<?php

namespace App\Actions\Reports;

use App\Models\ActivityVersion;
use App\Models\Document;
use App\Models\IncidentReport;
use App\Models\IncidentReportDraft;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\IncidentReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveIncidentReportDraft
{
    public function execute(User $actor, array $data, ?IncidentReportDraft $draft = null): IncidentReportDraft
    {
        Validator::make($data, ['title' => 'required|string|max:255',
            'details' => 'required|array:occurred_at,location,type,description,immediate_actions,current_status,persons,supporting_details,supporting_document_ids,official,position,issued_on,letterhead_text,letterhead_document_id',
            'details.occurred_at' => 'nullable|date', 'details.location' => 'nullable|string|max:1000', 'details.type' => 'nullable|string|max:255',
            'details.description' => 'nullable|string|max:6000', 'details.immediate_actions' => 'nullable|string|max:6000', 'details.current_status' => 'nullable|string|max:3000',
            'details.persons' => 'nullable|array|max:50', 'details.persons.*' => 'required|array:name,role,remarks',
            'details.persons.*.name' => 'required|string|max:150', 'details.persons.*.role' => 'required|string|max:150', 'details.persons.*.remarks' => 'nullable|string|max:1000',
            'details.supporting_details' => 'nullable|string|max:3000', 'details.supporting_document_ids' => 'nullable|array|max:25', 'details.supporting_document_ids.*' => 'integer|distinct',
            'details.official' => 'nullable|string|max:150', 'details.position' => 'nullable|string|max:150', 'details.issued_on' => 'nullable|date',
            'details.letterhead_text' => 'nullable|string|max:1000', 'details.letterhead_document_id' => 'nullable|integer',
            'supplements_report_id' => 'nullable|integer'])->validate();
        if (! $draft) {
            Validator::make($data, ['institution_id' => 'required|integer', 'activity_version_id' => 'required|integer'])->validate();
        }
        $version = ActivityVersion::findOrFail($draft?->activity_version_id ?? $data['activity_version_id']);
        $institutionId = $version->activity->institution_id;
        $access = app(Access::class);
        $access->authorize($actor, $institutionId, 'reports.submit');
        abort_unless($actor->can('panel.hei') && $access->owns($actor, $institutionId), 403);
        abort_if(isset($data['institution_id']) && (int) $data['institution_id'] !== $institutionId, 403);
        abort_if($draft && isset($data['activity_version_id']) && (int) $data['activity_version_id'] !== $draft->activity_version_id, 422, 'Create another draft to change the source activity revision.');
        $originalId = $draft?->supplements_report_id ?? ($data['supplements_report_id'] ?? null);
        if ($draft && array_key_exists('supplements_report_id', $data) && (int) $data['supplements_report_id'] !== (int) $draft->supplements_report_id) {
            throw ValidationException::withMessages(['supplements_report_id' => 'Create another draft to change the incident being supplemented.']);
        }
        $original = $originalId ? IncidentReport::findOrFail($originalId) : null;
        abort_if($original && $original->activity_id !== $version->activity_id, 403);
        $details = $data['details'];
        if (filled($details['occurred_at'] ?? null)) {
            $occurred = CarbonImmutable::parse($details['occurred_at'], config('lakbay.timezone'))->utc();
            if ($occurred->isFuture() || ($original && ! $original->occurred_at->equalTo($occurred))) {
                throw ValidationException::withMessages(['details.occurred_at' => 'Occurrence cannot be in the future; a supplement must retain the original incident time.']);
            }
        }
        foreach ($details['supporting_document_ids'] ?? [] as $id) {
            $document = Document::findOrFail($id);
            abort_unless($document->institution_id === $institutionId && $access->document($actor, $document) && $document->confidentiality !== 'restricted', 403);
            if ($document->scan_status !== 'clean') {
                throw ValidationException::withMessages(['details.supporting_document_ids' => 'Select clean supporting files. Report an initial notice without unavailable attachments.']);
            }
        }
        if ($id = $details['letterhead_document_id'] ?? null) {
            $image = Document::findOrFail($id);
            abort_unless($image->institution_id === $institutionId && $access->document($actor, $image), 403);
            abort_unless($image->scan_status === 'clean' && $image->confidentiality === 'institutional'
                && in_array($image->mime, ['image/png', 'image/jpeg'], true) && $image->size <= 2 * 1024 * 1024, 422, 'Choose a clean institutional PNG/JPG letterhead up to 2 MB.');
        }

        return DB::transaction(function () use ($actor, $data, $details, $draft, $version, $institutionId, $original): IncidentReportDraft {
            $values = ['title' => $data['title'], 'details' => $details, 'updated_by' => $actor->id];
            if ($draft) {
                $record = IncidentReportDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
                if ($record->incidentReport()->exists()) {
                    throw ValidationException::withMessages(['title' => 'This preparation has been filed; create a supplement instead of overwriting it.']);
                }
                $record->update($values + ['revision' => $record->revision + 1]);
            } else {
                $record = IncidentReportDraft::create($values + ['institution_id' => $institutionId, 'activity_id' => $version->activity_id,
                    'activity_version_id' => $version->id, 'supplements_report_id' => $original?->id,
                    'context' => app(IncidentReportBuilder::class)->context($version), 'created_by' => $actor->id]);
            }
            Audit::record($actor, $draft ? 'incident.draft_revised' : 'incident.draft_created', $record);

            return $record;
        });
    }
}
