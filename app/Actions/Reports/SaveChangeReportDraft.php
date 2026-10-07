<?php

namespace App\Actions\Reports;

use App\Models\ActivityVersion;
use App\Models\ChangeReport;
use App\Models\ChangeReportDraft;
use App\Models\Document;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\ChangeReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveChangeReportDraft
{
    public function execute(User $actor, array $data, ?ChangeReportDraft $draft = null): ChangeReportDraft
    {
        Validator::make($data, ['title' => 'required|string|max:255',
            'details' => 'required|array:changed_at,reason,supporting_details,supporting_document_ids,prepared_name,prepared_position,prepared_contact,prepared_email,reviewed_name,reviewed_position,noted_name,noted_position,letterhead_text,letterhead_document_id,original_submitted_on,original_submission_reference,other_changes',
            'details.changed_at' => 'nullable|date', 'details.reason' => 'nullable|string|max:6000',
            'details.supporting_details' => 'nullable|string|max:3000', 'details.supporting_document_ids' => 'nullable|array|max:25',
            'details.supporting_document_ids.*' => 'integer|distinct', 'details.prepared_name' => 'nullable|string|max:150',
            'details.prepared_position' => 'nullable|string|max:150', 'details.prepared_contact' => 'nullable|string|max:150',
            'details.prepared_email' => 'nullable|email|max:150', 'details.reviewed_name' => 'nullable|string|max:150',
            'details.reviewed_position' => 'nullable|string|max:150', 'details.noted_name' => 'nullable|string|max:150', 'details.noted_position' => 'nullable|string|max:150',
            'details.letterhead_text' => 'nullable|string|max:1000', 'details.letterhead_document_id' => 'nullable|integer',
            'details.original_submitted_on' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'details.original_submission_reference' => 'nullable|string|max:255',
            'details.other_changes' => 'nullable|array|max:15', 'details.other_changes.*' => 'required|array:label,previous,revised',
            'details.other_changes.*.label' => 'required|string|max:150', 'details.other_changes.*.previous' => 'required|string|max:1000',
            'details.other_changes.*.revised' => 'required|string|max:1000',
            'original_submission_id' => 'nullable|integer', 'supplements_report_id' => 'nullable|integer'])->validate();
        if (! $draft) {
            Validator::make($data, ['institution_id' => 'required|integer', 'original_version_id' => 'required|integer', 'revised_version_id' => 'required|integer'])->validate();
        }
        $old = ActivityVersion::findOrFail($draft?->original_version_id ?? $data['original_version_id']);
        $new = ActivityVersion::findOrFail($draft?->revised_version_id ?? $data['revised_version_id']);
        $institutionId = $old->activity->institution_id;
        $access = app(Access::class);
        $access->authorize($actor, $institutionId, 'reports.submit');
        abort_unless($actor->can('panel.hei') && $access->owns($actor, $institutionId), 403);
        abort_unless($new->activity_id === $old->activity_id && $new->version > $old->version, 422, 'Choose a later revision of the same activity.');
        abort_if(isset($data['institution_id']) && (int) $data['institution_id'] !== $institutionId, 403);
        foreach (['original_version_id', 'revised_version_id', 'original_submission_id', 'supplements_report_id'] as $key) {
            if ($draft && array_key_exists($key, $data) && (int) $data[$key] !== (int) $draft->$key) {
                throw ValidationException::withMessages([$key => 'Create another preparation to change its source references.']);
            }
        }
        $submissionId = $draft?->original_submission_id ?? ($data['original_submission_id'] ?? null);
        $submission = $submissionId ? Submission::findOrFail($submissionId) : null;
        abort_if($submission && ($submission->institution_id !== $institutionId || ! $submission->submitted_at || ! $submission->activityVersions()->where('activity_versions.id', $old->id)->exists()), 422, 'Select the receipt containing this original activity revision.');
        $details = $data['details'];
        if ($submission && (filled($details['original_submitted_on'] ?? null) || filled($details['original_submission_reference'] ?? null))) {
            throw ValidationException::withMessages(['details.original_submission_reference' => 'Electronic receipt details cannot be overridden by an external reference.']);
        }
        if (filled($details['changed_at'] ?? null) && CarbonImmutable::parse($details['changed_at'], config('lakbay.timezone'))->isFuture()) {
            throw ValidationException::withMessages(['details.changed_at' => 'Change time cannot be in the future.']);
        }
        $originalId = $draft?->supplements_report_id ?? ($data['supplements_report_id'] ?? null);
        $original = $originalId ? ChangeReport::findOrFail($originalId) : null;
        if ($original) {
            abort_unless($original->activity_id === $old->activity_id && $original->original_version_id === $old->id && $original->revised_version_id === $new->id, 422, 'A follow-up must describe the same original and revised activity pair.');
            if (filled($details['changed_at'] ?? null) && ! $original->changed_at->equalTo(CarbonImmutable::parse($details['changed_at'], config('lakbay.timezone'))->utc())) {
                throw ValidationException::withMessages(['details.changed_at' => 'A follow-up retains the original change time.']);
            }
        }
        foreach ($details['supporting_document_ids'] ?? [] as $id) {
            $document = Document::findOrFail($id);
            abort_unless($document->institution_id === $institutionId && $access->document($actor, $document) && $document->confidentiality === 'institutional', 403);
            if ($document->scan_status !== 'clean') {
                throw ValidationException::withMessages(['details.supporting_document_ids' => 'Choose clean institutional supporting files; do not delay immediate notice for evidence.']);
            }
        }
        if ($id = $details['letterhead_document_id'] ?? null) {
            $image = Document::findOrFail($id);
            abort_unless($image->institution_id === $institutionId && $access->document($actor, $image), 403);
            abort_unless($image->scan_status === 'clean' && $image->confidentiality === 'institutional' && in_array($image->mime, ['image/png', 'image/jpeg'], true) && $image->size <= 2 * 1024 * 1024, 422, 'Choose a clean institutional PNG/JPG letterhead up to 2 MB.');
        }

        return DB::transaction(function () use ($actor, $data, $details, $draft, $old, $new, $institutionId, $submission, $original): ChangeReportDraft {
            $values = ['title' => $data['title'], 'details' => $details, 'updated_by' => $actor->id];
            if ($draft) {
                $record = ChangeReportDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
                if ($record->changeReport()->exists()) {
                    throw ValidationException::withMessages(['title' => 'This preparation was filed. Create a follow-up instead of overwriting it.']);
                }
                $record->update($values + ['revision' => $record->revision + 1]);
            } else {
                $record = ChangeReportDraft::create($values + ['institution_id' => $institutionId, 'activity_id' => $old->activity_id,
                    'original_version_id' => $old->id, 'revised_version_id' => $new->id, 'original_submission_id' => $submission?->id,
                    'supplements_report_id' => $original?->id, 'context' => app(ChangeReportBuilder::class)->context($old, $new, $submission), 'created_by' => $actor->id]);
            }
            Audit::record($actor, $draft ? 'change.draft_revised' : 'change.draft_created', $record);

            return $record;
        });
    }
}
