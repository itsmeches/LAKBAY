<?php

namespace App\Services\Reporting;

use App\Models\ActivityVersion;
use App\Models\ChangeReportDraft;
use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use App\Services\Compliance\PolicyRules;

class ChangeReportBuilder
{
    /** @return array<string, mixed> */
    public function context(ActivityVersion $old, ActivityVersion $new, ?Submission $submission): array
    {
        $old->loadMissing(['activity.institution', 'personnel', 'itineraryItems']);
        $new->loadMissing(['personnel', 'itineraryItems']);
        $hei = $old->activity->institution;
        $template = FormTemplate::where('code', 'change')->whereHas('policyVersion', fn ($q) => $q->where('code', PolicyRules::CMO_CODE))->firstOrFail();
        $rows = [
            ['label' => 'Date and/or Time', 'previous' => $this->dates($old), 'revised' => $this->dates($new)],
            ['label' => 'Venue', 'previous' => $old->venue, 'revised' => $new->venue],
            ['label' => 'PIC/APIC', 'previous' => $this->personnel($old), 'revised' => $this->personnel($new)],
            ['label' => 'Number of Participants', 'previous' => $this->participants($old), 'revised' => $this->participants($new)],
        ];
        foreach (['title' => 'Activity title', 'category' => 'Declared category', 'requirement_category' => 'Requirement treatment',
            'responsible_office' => 'Responsible office', 'description' => 'Description', 'learning_objectives' => 'Learning objectives'] as $key => $label) {
            if ((string) $old->$key !== (string) $new->$key) {
                $rows[] = ['label' => 'Other: '.$label, 'previous' => (string) $old->$key, 'revised' => (string) $new->$key];
            }
        }
        $previous = $this->itinerary($old);
        $revised = $this->itinerary($new);
        if ($previous !== $revised) {
            $rows[] = ['label' => 'Other: Itinerary', 'previous' => $previous, 'revised' => $revised];
        }

        return ['renderer_version' => 1, 'original_version_id' => $old->id, 'revised_version_id' => $new->id,
            'original_revision' => $old->version, 'revised_revision' => $new->version,
            'template' => ['id' => $template->id, 'version' => $template->version, 'policy_version_id' => $template->policy_version_id, 'annex' => 'I', 'source_pages' => [29]],
            'header' => ['institution_id' => $hei->id, 'hei_name' => $hei->name, 'address' => $hei->address,
                'hei_type' => strtoupper($hei->institution_type), 'letterhead_text' => $hei->name."\n".$hei->address, 'letterhead' => null],
            'basic' => ['title' => $old->title, 'category' => $old->category],
            'original_submission' => $submission ? ['id' => $submission->id, 'reference' => $submission->reference,
                'date' => $submission->submitted_at?->timezone(config('lakbay.timezone'))->format('Y-m-d'), 'source' => 'electronic_receipt'] : null,
            'rows' => $rows];
    }

    private function dates(ActivityVersion $version): string
    {
        return $version->starts_at->timezone(config('lakbay.timezone'))->format('d M Y H:i').' - '.$version->ends_at->timezone(config('lakbay.timezone'))->format('d M Y H:i').' (Asia/Manila)';
    }

    private function participants(ActivityVersion $version): string
    {
        return ($version->student_count + ($version->other_count ?? 0)).' total ('.$version->student_count.' students; '.($version->other_count ?? 0).' others)';
    }

    private function personnel(ActivityVersion $version): string
    {
        return $version->personnel->map(fn ($p) => strtoupper($p->role).': '.$p->name.($p->position ? ' / '.$p->position : ''))->implode("\n") ?: 'Not recorded';
    }

    private function itinerary(ActivityVersion $version): string
    {
        return $version->itineraryItems->sortBy('sequence')->map(fn ($item) => 'Stop '.$item->sequence.': '
            .$item->starts_at->timezone(config('lakbay.timezone'))->format('d M Y H:i').' (Asia/Manila); '
            .$item->description.'; Location: '.$item->location
            .($item->locality ? '; Locality: '.$item->locality : '')
            .'; Responsible person: '.$item->responsible_person
            .($item->dot_accreditation ? '; DOT reference: '.$item->dot_accreditation : ''))->implode("\n") ?: 'Not recorded';
    }

    /** @return array<string, mixed> */
    public function snapshot(ChangeReportDraft $draft): array
    {
        $data = $draft->context;
        $details = $draft->details;
        $image = ! empty($details['letterhead_document_id']) ? Document::find($details['letterhead_document_id']) : null;
        $data['header']['letterhead'] = $image ? ['id' => $image->id, 'sha256' => $image->sha256] : null;
        if (filled($details['letterhead_text'] ?? null)) {
            $data['header']['letterhead_text'] = $details['letterhead_text'];
        }
        if (! $data['original_submission'] && filled($details['original_submitted_on'] ?? null) && filled($details['original_submission_reference'] ?? null)) {
            $data['original_submission'] = ['id' => null, 'date' => $details['original_submitted_on'], 'reference' => $details['original_submission_reference'], 'source' => 'hei_entered_external_reference'];
        }
        foreach ($details['other_changes'] ?? [] as $row) {
            $data['rows'][] = ['label' => 'Other: '.$row['label'], 'previous' => $row['previous'], 'revised' => $row['revised']];
        }
        if (! collect($data['rows'])->contains(fn ($row) => str_starts_with($row['label'], 'Other:'))) {
            $data['rows'][] = ['label' => 'Other: (please specify)', 'previous' => '', 'revised' => ''];
        }
        $data['draft_id'] = $draft->id;
        $data['draft_revision'] = $draft->revision;
        $data['supplements_report_id'] = $draft->supplements_report_id;
        $data['details'] = $details;
        $data['supporting_documents'] = Document::whereIn('id', $details['supporting_document_ids'] ?? [])->orderBy('id')->get()
            ->map(fn (Document $document) => ['id' => $document->id, 'name' => $document->original_name, 'sha256' => $document->sha256])->all();

        return $data;
    }

    /** @param array<string, mixed> $data
     * @return list<string>
     */
    public function missingFields(array $data): array
    {
        $missing = [];
        foreach (['reason' => 'Reason for change', 'prepared_name' => 'Prepared by', 'prepared_position' => 'Preparer designation',
            'prepared_contact' => 'Preparer contact number', 'prepared_email' => 'Preparer email', 'reviewed_name' => 'Reviewed by',
            'reviewed_position' => 'Reviewer designation', 'noted_name' => 'Noted by', 'noted_position' => 'Noting official designation'] as $key => $label) {
            if (blank($data['details'][$key] ?? null)) {
                $missing[] = $label;
            }
        }
        if (blank($data['header']['address'] ?? null)) {
            $missing[] = 'HEI profile address';
        }
        if (blank($data['original_submission']['date'] ?? null)) {
            $missing[] = 'Original submission date and reference';
        }

        return $missing;
    }

    public function authorize(User $actor, int $institutionId): void
    {
        app(Access::class)->authorize($actor, $institutionId, 'records.view');
    }

    /** @param array<string, mixed> $data */
    public function letterhead(User $actor, array $data): ?string
    {
        return app(ClocaBuilder::class)->letterhead($actor, $data);
    }
}
