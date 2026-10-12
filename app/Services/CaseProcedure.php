<?php

namespace App\Services;

use App\Models\CaseRecord;
use App\Models\Region;

class CaseProcedure
{
    /** @return array<string, string> */
    public static function stages(): array
    {
        return ['received' => 'Received', 'referred' => 'CHEDRO jurisdiction referral', 'investigating' => 'Authoritative ongoing investigation',
            'fact_finding' => 'Fact-finding evidence', 'investigation_findings' => 'Investigation findings', 'committee_recommendation' => 'CHED Committee recommendation',
            'osds_referral' => 'OSDS correspondence', 'decided' => 'CEB decision', 'hei_dispatched' => 'Written notice dispatched to HEI',
            'hei_received' => 'HEI receipt evidence', 'closed' => 'Signed closure'];
    }

    /** @return array<string> */
    public function missing(CaseRecord $case): array
    {
        $types = $case->events()->whereNotNull('document_id')->pluck('type')->all();

        return array_values(array_diff(['referred', 'fact_finding', 'investigation_findings', 'committee_recommendation', 'decided', 'hei_dispatched', 'hei_received'], $types));
    }

    public function history(CaseRecord $case): string
    {
        $missing = array_map(fn (string $type): string => self::stages()[$type], $this->missing($case));
        $lines = [$missing ? 'Not recorded: '.implode('; ', $missing).'. Unknown history is not evidence of a missing external procedure.' : 'All tracked procedural stages are recorded. This is not a legal finding.'];
        foreach ($case->events()->with('document')->orderBy('occurred_at')->orderBy('id')->get() as $event) {
            $lines[] = '#'.$event->id.' '.(self::stages()[$event->type] ?? $event->type).' — '.$event->occurred_at->setTimezone(config('lakbay.timezone'))->format('d M Y H:i').' (Asia/Manila)';
            $lines[] = $event->notes;
            $lines[] = 'Recorded by user #'.$event->created_by.'; evidence: '.($event->document?->original_name ?? 'None recorded');
            if (! $event->procedure) {
                $lines[] = 'Legacy event: structured particulars were not recorded.';

                continue;
            }
            foreach (['reference' => 'Reference', 'authority' => 'Issuing authority', 'jurisdiction_basis' => 'Jurisdiction basis', 'activity_location' => 'Activity location',
                'recipient' => 'HEI recipient', 'channel' => 'Dispatch channel', 'decision_event_id' => 'Decision event', 'dispatch_event_id' => 'Dispatch event', 'history_explanation' => 'Historical explanation'] as $key => $label) {
                if (! empty($event->procedure[$key])) {
                    $lines[] = $label.': '.$event->procedure[$key];
                }
            }
            if (! empty($event->procedure['destination_region_id'])) {
                $lines[] = 'Referral destination: '.(Region::find($event->procedure['destination_region_id'])?->name ?? 'Region #'.$event->procedure['destination_region_id']);
            }
            if (! empty($event->procedure['missing_prior_stages'])) {
                $lines[] = 'Earlier stages unavailable at recording: '.implode('; ', array_map(fn ($type) => self::stages()[$type] ?? $type, $event->procedure['missing_prior_stages']));
            }
        }

        return implode("\n\n", $lines);
    }
}
