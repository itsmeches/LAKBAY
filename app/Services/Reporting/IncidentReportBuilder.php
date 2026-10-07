<?php

namespace App\Services\Reporting;

use App\Models\ActivityVersion;
use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\IncidentReportDraft;
use App\Models\User;
use App\Services\Access;
use App\Services\Compliance\PolicyRules;

class IncidentReportBuilder
{
    public const CERTIFICATION = 'I hereby certify that the foregoing information is true and correct to the best of my knowledge and based on the information available at the time of the submission of this Report. Any available supporting documents are attached, while additional documents, if any, shall be made available to the Commission on Higher Education (CHED), whenever required.';

    /** @return array<string, mixed> */
    public function context(ActivityVersion $version): array
    {
        $version->loadMissing(['activity.institution', 'personnel']);
        $institution = $version->activity->institution;
        $template = FormTemplate::where('code', 'incident')->whereHas('policyVersion', fn ($query) => $query->where('code', PolicyRules::CMO_CODE))->firstOrFail();

        return [
            'renderer_version' => 1,
            'activity_version_id' => $version->id, 'activity_revision' => $version->version,
            'template' => ['id' => $template->id, 'version' => $template->version, 'policy_version_id' => $template->policy_version_id, 'annex' => 'H', 'source_pages' => [28]],
            'header' => ['institution_id' => $institution->id, 'hei_name' => $institution->name,
                'address' => $institution->address, 'hei_type' => strtoupper($institution->institution_type),
                'letterhead_text' => $institution->name."\n".$institution->address, 'letterhead' => null],
            'basic' => ['title' => $version->title, 'category' => $version->category,
                'proposed_dates' => $version->starts_at->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' - '.$version->ends_at->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' (Asia/Manila)',
                'venue' => $version->venue, 'participants' => $version->student_count + ($version->other_count ?? 0),
                'office' => $version->responsible_office, 'pic' => $version->personnel->where('role', 'pic')->pluck('name')->implode(', '),
                'apic' => $version->personnel->where('role', 'apic')->pluck('name')->implode(', ') ?: 'Not applicable'],
            'certification' => self::CERTIFICATION,
        ];
    }

    /** @return array<string, mixed> */
    public function snapshot(IncidentReportDraft $draft): array
    {
        $data = $draft->context;
        $details = $draft->details;
        $image = ! empty($details['letterhead_document_id']) ? Document::find($details['letterhead_document_id']) : null;
        $data['header']['letterhead'] = $image ? ['id' => $image->id, 'sha256' => $image->sha256] : null;
        if (filled($details['letterhead_text'] ?? null)) {
            $data['header']['letterhead_text'] = $details['letterhead_text'];
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
        foreach (['occurred_at' => 'Incident date and time', 'location' => 'Exact location', 'type' => 'Type of incident',
            'description' => 'Brief description', 'immediate_actions' => 'Immediate actions', 'current_status' => 'Current status',
            'official' => 'Authorized HEI official', 'position' => 'Designation / position'] as $key => $label) {
            if (blank($data['details'][$key] ?? null)) {
                $missing[] = $label;
            }
        }
        if (blank($data['header']['address'] ?? null)) {
            $missing[] = 'HEI profile address';
        }
        foreach (['office' => 'Responsible office', 'pic' => 'PIC'] as $key => $label) {
            if (blank($data['basic'][$key] ?? null)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    public function authorize(User $actor, int $institutionId): void
    {
        $access = app(Access::class);
        $access->authorize($actor, $institutionId, 'records.view');
        abort_unless($access->owns($actor, $institutionId) || ($actor->can('incidents.view') && $access->regional($actor, $institutionId)), 403);
    }

    /** @param array<string, mixed> $data */
    public function letterhead(User $actor, array $data): ?string
    {
        return app(ClocaBuilder::class)->letterhead($actor, $data);
    }
}
