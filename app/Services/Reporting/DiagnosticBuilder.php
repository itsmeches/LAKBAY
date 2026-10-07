<?php

namespace App\Services\Reporting;

use App\Models\ActivityVersion;
use App\Models\Document;
use App\Models\User;

class DiagnosticBuilder
{
    /** @return array<string, mixed>|null */
    public function snapshot(ActivityVersion $version): ?array
    {
        $diagnostic = $version->diagnostic;
        if (! $diagnostic) {
            return null;
        }
        $version->loadMissing(['activity.institution', 'personnel']);
        $institution = $version->activity->institution;
        $image = ! empty($diagnostic['letterhead_document_id']) ? Document::find($diagnostic['letterhead_document_id']) : null;

        return [
            'renderer_version' => 1, 'activity_version_id' => $version->id, 'activity_revision' => $version->version,
            'criteria_version' => $diagnostic['criteria_version'], 'template' => $diagnostic['template'],
            'header' => ['institution_id' => $institution->id, 'hei_name' => $institution->name,
                'address' => $institution->address, 'hei_type' => strtoupper($institution->institution_type),
                'letterhead_text' => ($diagnostic['letterhead_text'] ?? null) ?: $institution->name."\n".$institution->address,
                'letterhead' => $image ? ['id' => $image->id, 'sha256' => $image->sha256] : null],
            'basic' => ['title' => $version->title, 'category' => $version->category,
                'proposed_dates' => $version->starts_at->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' - '.$version->ends_at->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' (Asia/Manila)',
                'participants' => $version->student_count + ($version->other_count ?? 0), 'venue' => $version->venue,
                'office' => $version->responsible_office, 'pic' => $version->personnel->where('role', 'pic')->pluck('name')->implode(', '),
                'apic' => $version->personnel->where('role', 'apic')->pluck('name')->implode(', ') ?: 'Not applicable'],
            'groups' => $diagnostic['groups'], 'answers' => $diagnostic['answers'] ?? [],
            'recommendations' => $diagnostic['recommendations'], 'recommendation' => $diagnostic['recommendation'] ?? null,
            'signatories' => $diagnostic['signatories'] ?? [],
        ];
    }

    /** @param array<string, mixed> $data
     * @return list<string>
     */
    public function missingFields(array $data): array
    {
        $missing = [];
        foreach ($data['groups'] as $criteria) {
            foreach ($criteria as $code => $question) {
                if (! in_array(data_get($data, 'answers.'.$code.'.answer'), ['yes', 'no'], true)) {
                    $missing[] = $question;
                }
            }
        }
        if (! isset($data['recommendations'][$data['recommendation'] ?? ''])) {
            $missing[] = 'HEI summary and recommendation';
        }
        foreach (['prepared_by', 'reviewed_by', 'noted_by'] as $role) {
            foreach (['name', 'position'] as $field) {
                if (blank(data_get($data, 'signatories.'.$role.'.'.$field))) {
                    $missing[] = str_replace('_', ' ', ucfirst($role)).' '.$field;
                }
            }
        }
        foreach (['contact', 'email'] as $field) {
            if (blank(data_get($data, 'signatories.prepared_by.'.$field))) {
                $missing[] = 'Preparer '.$field;
            }
        }
        if (blank($data['header']['address'])) {
            $missing[] = 'HEI profile address';
        }

        return $missing;
    }

    /** @param array<string, mixed> $data */
    public function letterhead(User $actor, array $data): ?string
    {
        return app(ClocaBuilder::class)->letterhead($actor, $data);
    }
}
