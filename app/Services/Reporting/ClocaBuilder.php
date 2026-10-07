<?php

namespace App\Services\Reporting;

use App\Models\Calendar;
use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Access;
use App\Services\Compliance\PolicyRules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ClocaBuilder
{
    /** @return array<string, mixed> */
    public function snapshot(Calendar $calendar, ?Collection $versions = null): array
    {
        $calendar->loadMissing(['institution', 'academicPeriod']);
        $versions ??= $calendar->activities()->with(['latestVersion.personnel'])->orderBy('id')->get()->pluck('latestVersion')->filter();
        $details = $calendar->annex_b ?? [];
        $address = ($details['address'] ?? null) ?: $calendar->institution->address;
        $image = ! empty($details['letterhead_document_id']) ? Document::find($details['letterhead_document_id']) : null;
        $template = FormTemplate::where('code', 'cloca')->whereHas('policyVersion', fn ($query) => $query->where('code', PolicyRules::CMO_CODE))->first();
        $rows = $versions->map(function ($version): array {
            $fields = $version->cloca ?? [];
            $start = $version->starts_at->setTimezone(config('lakbay.timezone'));
            $end = $version->ends_at->setTimezone(config('lakbay.timezone'));
            $personnel = $version->personnel;
            $locations = collect($fields['locations'] ?? [])->map(fn ($location) => $location['name'].' ('.($location['region_scope'] === 'within' ? 'within region' : 'outside region').')')->implode("\n");

            return [
                'activity_id' => $version->activity_id, 'activity_version_id' => $version->id,
                'month' => $start->format('M Y').($start->format('Y-m') !== $end->format('Y-m') ? ' - '.$end->format('M Y') : ''),
                'category' => $version->category,
                'type' => $fields['activity_type_code'] ?? data_get($version->preparation, 'activity_type_code'),
                'name' => $version->title,
                'description' => $fields['brief_description'] ?? $version->description,
                'pic' => $personnel->where('role', 'pic')->pluck('name')->implode("\n"),
                'apic' => $personnel->where('role', 'apic')->pluck('name')->implode("\n") ?: 'NA',
                'contacts' => $personnel->whereIn('role', ['pic', 'apic'])->map(fn ($person) => $person->name.': '.$person->contact)->implode("\n"),
                'days' => $fields['duration_days'] ?? null,
                'suggested_days' => (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1,
                'locations' => $locations ?: $version->venue,
                'locations_confirmed' => $locations !== '',
                'participants' => $version->student_count + $version->other_count,
                'coordination' => $fields['agency_coordination'] ?? null,
                'assistance' => $fields['requested_assistance'] ?? data_get($version->preparation, 'agency_assistance'),
            ];
        })->values()->all();

        return [
            'renderer_version' => 1,
            'template' => ['annex' => 'B', 'source' => 'CMO No. 24, s. 2026', 'source_page' => 21,
                'id' => $template?->id, 'version' => $template?->version ?? 1, 'policy_version_id' => $template?->policy_version_id],
            'header' => [
                'institution_id' => $calendar->institution_id, 'hei_name' => $calendar->institution->name,
                'hei_type' => match ($calendar->institution->institution_type) {
                    'suc' => 'SUC', 'luc' => 'LUC', default => 'Private'
                },
                'address' => $address,
                'effective_on' => $calendar->effective_on?->format('Y-m-d'),
                'academic_year' => ($details['academic_year'] ?? null) ?: $calendar->academicPeriod->label,
                'semester' => ($details['semester'] ?? null) ?: $calendar->academicPeriod->term,
                'office' => $calendar->responsible_office,
                'letterhead_text' => ($details['letterhead_text'] ?? null) ?: $calendar->institution->name."\n".$address,
                'letterhead' => $image ? ['id' => $image->id, 'sha256' => $image->sha256] : null,
                'prepared_by' => $details['prepared_by'] ?? [],
                'reviewed_by' => $details['reviewed_by'] ?? [],
                'noted_by' => $details['noted_by'] ?? [],
            ],
            'rows' => $rows,
        ];
    }

    /** @param array<string, mixed> $data
     * @return list<string>
     */
    public function missingFields(array $data): array
    {
        $missing = [];
        foreach (['hei_name' => 'HEI name', 'address' => 'HEI address', 'effective_on' => 'Calendar effectivity',
            'academic_year' => 'Academic year', 'semester' => 'Term / semester', 'office' => 'Responsible office'] as $key => $label) {
            if (blank($data['header'][$key] ?? null)) {
                $missing[] = $label;
            }
        }
        foreach (['prepared_by', 'reviewed_by', 'noted_by'] as $key) {
            foreach (['name', 'position'] as $field) {
                if (blank(data_get($data, 'header.'.$key.'.'.$field))) {
                    $missing[] = str_replace('_', ' ', ucfirst($key)).' '.$field;
                }
            }
        }
        if (! $data['rows']) {
            $missing[] = 'At least one activity';
        }
        foreach ($data['rows'] as $index => $row) {
            foreach (['type', 'description', 'pic', 'contacts', 'days', 'coordination', 'assistance'] as $key) {
                if (blank($row[$key] ?? null)) {
                    $missing[] = 'Activity '.($index + 1).' ('.$row['name'].'): '.$key
                        .($key === 'days' ? ' (suggested inclusive calendar days: '.$row['suggested_days'].'; confirm in the activity plan)' : '');
                }
            }
            if (! $row['locations_confirmed']) {
                $missing[] = 'Activity '.($index + 1).': locations with within/outside-region indicators';
            }
            foreach (['description', 'locations', 'pic', 'apic', 'contacts', 'coordination', 'assistance'] as $key) {
                if (mb_strlen((string) ($row[$key] ?? '')) > 1200) {
                    $missing[] = 'Activity '.($index + 1).': shorten '.$key.' to 1200 characters for a printable row';
                }
            }
        }

        return $missing;
    }

    /** @param array<string, mixed> $data */
    public function letterhead(User $actor, array $data): ?string
    {
        $reference = $data['header']['letterhead'] ?? null;
        if (! $reference) {
            return null;
        }
        $document = Document::findOrFail($reference['id']);
        abort_unless($document->institution_id === $data['header']['institution_id'] && app(Access::class)->document($actor, $document), 403);
        abort_unless($document->scan_status === 'clean' && $document->confidentiality === 'institutional'
            && in_array($document->mime, ['image/png', 'image/jpeg'], true) && $document->size <= 2 * 1024 * 1024, 409, 'Letterhead image is unavailable or quarantined.');
        abort_unless(Storage::disk($document->disk)->exists($document->path), 409, 'Letterhead image is unavailable.');
        $bytes = Storage::disk($document->disk)->get($document->path);
        abort_unless(strlen($bytes) <= 2 * 1024 * 1024 && hash_equals($reference['sha256'], hash('sha256', $bytes)), 409, 'Letterhead image no longer matches its recorded hash.');

        return 'data:'.$document->mime.';base64,'.base64_encode($bytes);
    }
}
