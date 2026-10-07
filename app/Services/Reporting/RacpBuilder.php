<?php

namespace App\Services\Reporting;

use App\Models\ActivityVersion;
use App\Models\Document;
use App\Models\User;

class RacpBuilder
{
    /** @return array<string, mixed>|null */
    public function snapshot(ActivityVersion $version): ?array
    {
        $racp = $version->racp;
        if (! $racp) {
            return null;
        }
        $version->loadMissing(['activity.institution', 'personnel']);
        $institution = $version->activity->institution;
        $image = ! empty($racp['letterhead_document_id']) ? Document::find($racp['letterhead_document_id']) : null;

        return [
            'renderer_version' => 1, 'activity_version_id' => $version->id, 'activity_revision' => $version->version,
            'assessment_version' => $racp['assessment_version'], 'template' => $racp['template'],
            'header' => ['institution_id' => $institution->id, 'hei_name' => $institution->name,
                'address' => $institution->address, 'hei_type' => strtoupper($institution->institution_type),
                'letterhead_text' => ($racp['letterhead_text'] ?? null) ?: $institution->name."\n".$institution->address,
                'letterhead' => $image ? ['id' => $image->id, 'sha256' => $image->sha256] : null],
            'basic' => ['title' => $version->title, 'category' => $version->category,
                'proposed_dates' => $version->starts_at->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' - '.$version->ends_at->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' (Asia/Manila)',
                'participants' => $version->student_count + ($version->other_count ?? 0), 'venue' => $version->venue,
                'office' => $version->responsible_office, 'pic' => $version->personnel->where('role', 'pic')->pluck('name')->implode(', '),
                'apic' => $version->personnel->where('role', 'apic')->pluck('name')->implode(', ') ?: 'Not applicable'],
            'areas' => $racp['areas'], 'risks' => $racp['risks'] ?? [], 'other_risks' => $racp['other_risks'] ?? [],
            'offices' => $racp['offices'], 'contacts' => $racp['contacts'] ?? [], 'other_contacts' => $racp['other_contacts'] ?? [],
            'certification' => $racp['certification'], 'issued_on' => $racp['issued_on'] ?? null,
            'signatories' => $racp['signatories'] ?? [],
        ];
    }

    /** @param array<string, mixed> $data
     * @return list<string>
     */
    public function missingFields(array $data): array
    {
        $missing = [];
        foreach ($data['areas'] as $code => $label) {
            if (! in_array(data_get($data, 'risks.'.$code.'.level'), ['low', 'medium', 'high'], true)) {
                $missing[] = $label.': risk level';
            }
            if (blank(data_get($data, 'risks.'.$code.'.measure'))) {
                $missing[] = $label.': contingency measure';
            }
        }
        foreach ($data['other_risks'] as $index => $risk) {
            if (! in_array($risk['level'] ?? null, ['low', 'medium', 'high'], true) || blank($risk['measure'] ?? null)) {
                $missing[] = 'Other risk '.($index + 1).': level and contingency measure';
            }
        }
        foreach ($data['offices'] as $code => $label) {
            foreach (['person', 'details'] as $field) {
                if (blank(data_get($data, 'contacts.'.$code.'.'.$field))) {
                    $missing[] = $label.': contact '.$field;
                }
            }
        }
        foreach ($data['other_contacts'] as $index => $contact) {
            if (blank($contact['person'] ?? null) || blank($contact['details'] ?? null)) {
                $missing[] = 'Other agency/office '.($index + 1).': contact person and details';
            }
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
