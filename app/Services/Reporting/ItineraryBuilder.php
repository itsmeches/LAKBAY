<?php

namespace App\Services\Reporting;

use App\Models\ActivityVersion;
use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Compliance\PolicyRules;

class ItineraryBuilder
{
    /** @return array<string, mixed>|null */
    public function snapshot(ActivityVersion $version): ?array
    {
        $itinerary = $version->annex_j;
        if (! $itinerary && ! $version->itineraryItems()->exists()) {
            return null;
        }
        $version->loadMissing(['activity.institution', 'personnel', 'itineraryItems']);
        $institution = $version->activity->institution;
        $image = ! empty($itinerary['letterhead_document_id']) ? Document::find($itinerary['letterhead_document_id']) : null;

        return [
            'renderer_version' => 1, 'activity_version_id' => $version->id, 'activity_revision' => $version->version,
            'template' => $itinerary['template'] ?? $this->template(),
            'header' => ['institution_id' => $institution->id, 'hei_name' => $institution->name,
                'address' => $institution->address, 'hei_type' => strtoupper($institution->institution_type),
                'letterhead_text' => ($itinerary['letterhead_text'] ?? null) ?: $institution->name."\n".$institution->address,
                'letterhead' => $image ? ['id' => $image->id, 'sha256' => $image->sha256] : null],
            'basic' => ['title' => $version->title, 'category' => $version->category,
                'proposed_dates' => $version->starts_at->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' - '.$version->ends_at->timezone(config('lakbay.timezone'))->format('d M Y, H:i').' (Asia/Manila)',
                'participants' => $version->student_count + ($version->other_count ?? 0), 'venue' => $version->venue,
                'office' => $version->responsible_office, 'pic' => $version->personnel->where('role', 'pic')->pluck('name')->implode(', '),
                'apic' => $version->personnel->where('role', 'apic')->pluck('name')->implode(', ') ?: 'Not applicable'],
            'rows' => $version->itineraryItems->sortBy('sequence')->map(fn ($item) => [
                'date' => $item->starts_at->timezone(config('lakbay.timezone'))->format('d M Y'),
                'time' => $item->starts_at->timezone(config('lakbay.timezone'))->format('H:i'),
                'activity' => $item->description, 'location' => $item->location,
                'dot_accreditation' => $item->dot_accreditation, 'responsible_person' => $item->responsible_person,
            ])->values()->all(),
            'signatories' => $itinerary['signatories'] ?? [],
        ];
    }

    /** @param array<string, mixed> $data
     * @return list<string>
     */
    public function missingFields(array $data): array
    {
        $missing = [];
        if (empty($data['rows'])) {
            $missing[] = 'At least one itinerary row';
        }
        foreach ($data['rows'] as $index => $row) {
            foreach (['date', 'time', 'activity', 'location', 'responsible_person'] as $field) {
                if (blank($row[$field] ?? null)) {
                    $missing[] = 'Itinerary row '.($index + 1).' '.str_replace('_', ' ', $field);
                }
            }
        }
        foreach (['office' => 'Responsible office', 'pic' => 'PIC'] as $key => $label) {
            if (blank($data['basic'][$key] ?? null)) {
                $missing[] = $label;
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

    /** @return array<string, mixed> */
    private function template(): array
    {
        $template = FormTemplate::where('code', 'itinerary')->whereHas('policyVersion', fn ($q) => $q->where('code', PolicyRules::CMO_CODE))->firstOrFail();

        return ['id' => $template->id, 'version' => $template->version, 'policy_version_id' => $template->policy_version_id, 'annex' => 'J', 'source_page' => 30];
    }
}
