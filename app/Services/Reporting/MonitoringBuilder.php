<?php

namespace App\Services\Reporting;

use App\Models\Institution;
use App\Models\MonitoringRecord;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;

class MonitoringBuilder
{
    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function snapshot(User $actor, array $data): array
    {
        $institution = Institution::findOrFail($data['institution_id']);
        $metadata = $data['annex_metadata'] ?? [];
        $items = collect($data['items'])->sortBy(fn ($item) => (int) $item['criterion_code'])
            ->map(function (array $item): array {
                $definition = config('monitoring.annex_m.'.$item['criterion_code']);

                return ['code' => (string) $item['criterion_code'], 'criterion' => $definition['criterion'],
                    'compliance' => $item['compliance'], 'verification' => $item['verification'],
                    'suggested_verification' => $definition['verification'], 'remarks' => $item['remarks'] ?? null];
            })->values()->all();

        return [
            'renderer_version' => 1, 'template' => ['annex' => 'M', 'source' => 'CMO No. 24, s. 2026', 'source_pages' => [33, 34], 'version' => 1],
            'institution_id' => $institution->id, 'hei_name' => $institution->name,
            'hei_type' => match ($institution->institution_type) {
                'suc' => 'SUC', 'luc' => 'LUC', default => 'Private'
            },
            'region' => Region::findOrFail($actor->region_id)->name, 'region_id' => $actor->region_id,
            'period' => $data['period'], 'monitored_on' => CarbonImmutable::parse($data['monitored_on'])->format('Y-m-d'),
            'monitored_by' => $actor->name, 'monitored_by_id' => $actor->id,
            'monitoring_type' => match ($data['type']) {
                'desk' => 'Desk review', 'virtual' => 'Virtual monitoring', 'on_site' => 'On-site monitoring', 'incident_based' => 'Incident-based monitoring'
            },
            'respondent' => $data['respondent'], 'respondent_position' => $metadata['respondent_position'] ?? null,
            'items' => $items,
            'findings' => ['Good practices' => $data['good_practices'] ?? null, 'Deficiencies noted' => $data['findings'],
                'Suggested Measures for Continuous Improvement' => $data['suggestions'] ?? null,
                'Others (please specify)' => $metadata['other_findings'] ?? null],
            'signatories' => [
                'Prepared by' => ['name' => $metadata['prepared_by'] ?? null, 'position' => $metadata['prepared_position'] ?? null],
                'Reviewed by' => ['name' => $metadata['reviewed_by'] ?? null, 'position' => $metadata['reviewed_position'] ?? null],
                'Noted by' => ['name' => $metadata['noted_by'] ?? null, 'position' => $metadata['noted_position'] ?? null],
            ],
            'hei_conforme' => ['name' => $metadata['hei_conforme'] ?? null, 'date' => $metadata['hei_conforme_date'] ?? null],
        ];
    }

    /** @param array<string, mixed> $data
     * @return list<array<string, string>>
     */
    public function printableItems(array $data, int $from, int $until): array
    {
        $rows = [];
        foreach (array_slice($data['items'], $from, $until - $from) as $item) {
            $verification = mb_str_split($item['verification'], 320);
            $remarks = mb_str_split($item['remarks'] ?? '', 250);
            $count = max(count($verification), count($remarks), 1);
            for ($i = 0; $i < $count; $i++) {
                $rows[] = ['criterion' => $item['code'].'. '.$item['criterion'].($i ? ' (continued)' : ''),
                    'compliance' => $i ? '' : ucfirst($item['compliance']), 'verification' => $verification[$i] ?? '', 'remarks' => $remarks[$i] ?? ''];
            }
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    public function preserved(MonitoringRecord $record): array
    {
        abort_unless(($record->annex_m['renderer_version'] ?? null) === 1, 409,
            'No frozen Annex M output exists for this historical record. Use its original signed evidence.');

        return $record->annex_m;
    }
}
