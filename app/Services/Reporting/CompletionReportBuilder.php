<?php

namespace App\Services\Reporting;

use App\Models\ActivityVersion;
use App\Models\CompletionReportDraft;
use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Access;
use App\Services\Compliance\PolicyRules;

class CompletionReportBuilder
{
    public const CERTIFICATION = 'I hereby certify that the foregoing information is true and correct. Any supporting documents related to this Report are complete and available for verification, monitoring, inspection, or submission to the Commission on Higher Education (CHED), whenever required.';

    /** @return array<string, mixed> */
    public function context(ActivityVersion $version): array
    {
        $version->loadMissing(['activity.institution', 'personnel']);
        $institution = $version->activity->institution;
        $template = FormTemplate::where('code', 'completion')->whereHas('policyVersion', fn ($query) => $query->where('code', PolicyRules::CMO_CODE))->firstOrFail();

        return [
            'renderer_version' => 1,
            'activity_version_id' => $version->id, 'activity_revision' => $version->version,
            'template' => ['id' => $template->id, 'version' => $template->version, 'policy_version_id' => $template->policy_version_id, 'annex' => 'G', 'source_pages' => [27]],
            'header' => ['institution_id' => $institution->id, 'hei_name' => $institution->name,
                'address' => $institution->address, 'hei_type' => strtoupper($institution->institution_type),
                'letterhead_text' => $institution->name."\n".$institution->address, 'letterhead' => null],
            'basic' => ['title' => $version->title, 'category' => $version->category,
                'proposed_dates' => $version->starts_at->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' - '.$version->ends_at->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' (Asia/Manila)',
                'office' => $version->responsible_office, 'pic' => $version->personnel->where('role', 'pic')->pluck('name')->implode(', '),
                'apic' => $version->personnel->where('role', 'apic')->pluck('name')->implode(', ') ?: 'Not applicable'],
            'certification' => self::CERTIFICATION,
        ];
    }

    /** @return array<string, mixed> */
    public function snapshot(CompletionReportDraft $draft): array
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
        $data['details'] = $details;
        $data['basic']['venue'] = $details['venue'] ?? '';
        $data['basic']['participants'] = (int) ($details['student_count'] ?? 0) + (int) ($details['other_count'] ?? 0);

        return $data;
    }

    /** @param array<string, mixed> $data
     * @return list<string>
     */
    public function missingFields(array $data): array
    {
        $missing = [];
        foreach (['ended_at' => 'Actual conclusion', 'venue' => 'Actual venue / locations', 'student_count' => 'Student participants',
            'other_count' => 'Other participants', 'implementation' => 'Implementation status', 'summary' => 'Summary and outcome',
            'official' => 'Authorized HEI official', 'position' => 'Designation / position'] as $key => $label) {
            if (! array_key_exists($key, $data['details']) || blank($data['details'][$key])) {
                $missing[] = $label;
            }
        }
        if (($data['details']['implementation'] ?? null) === 'with_changes' && blank($data['details']['change_explanation'] ?? null)) {
            $missing[] = 'Explanation of changes';
        }
        if (! array_key_exists('incidents_encountered', $data['details'])) {
            $missing[] = 'Incident declaration';
        } elseif ($data['details']['incidents_encountered'] && blank($data['details']['incident_explanation'] ?? null)) {
            $missing[] = 'Brief incident explanation';
        }
        foreach (['address' => 'HEI profile address'] as $key => $label) {
            if (blank($data['header'][$key] ?? null)) {
                $missing[] = $label;
            }
        }
        foreach (['office' => 'Responsible office', 'pic' => 'PIC'] as $key => $label) {
            if (blank($data['basic'][$key] ?? null)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    public function authorize(User $actor, int $institutionId, bool $incidents): void
    {
        $access = app(Access::class);
        $access->authorize($actor, $institutionId, 'records.view');
        abort_if($incidents && ! $access->owns($actor, $institutionId) && ! $actor->can('incidents.view'), 403);
    }

    /** @param array<string, mixed> $data */
    public function letterhead(User $actor, array $data): ?string
    {
        return app(ClocaBuilder::class)->letterhead($actor, $data);
    }
}
