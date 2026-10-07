<?php

namespace App\Services\Compliance;

use App\Models\ActivityVersion;
use App\Models\Document;
use Carbon\CarbonImmutable;

class ActivityCompliance
{
    public function issues(ActivityVersion $version, bool $preActivity = true): array
    {
        $issues = [];
        $conditions = $version->conditions;
        if (in_array($version->requirement_category, [1, 2], true)) {
            if (! array_key_exists('requires_physical_activity', $conditions) || ! array_key_exists('requires_travel', $conditions)) {
                $issues[] = 'Confirm physical-activity and travel facts for the PIC ratio.';
            }
            $ratio = (($conditions['requires_physical_activity'] ?? false) || ($conditions['requires_accommodation'] ?? false) || ($conditions['requires_travel'] ?? false)) ? 30 : 50;
            $pics = $version->personnel->where('role', 'pic');
            if ($pics->count() < (int) ceil($version->student_count / $ratio)) {
                $issues[] = 'Designate at least '.(int) ceil($version->student_count / $ratio).' PIC(s) for the 1:'.$ratio.' ratio. APICs are not counted.';
            }
            foreach ($pics as $pic) {
                if (! $pic->hei_employed || ! $pic->accompanies_activity || ! $pic->first_aid_trained || ! trim($pic->qualifications ?? '') || ! trim($pic->first_aid_reference ?? '')) {
                    $issues[] = 'Record HEI employment, accompaniment, relevant qualifications and first-aid evidence for '.$pic->name.'.';
                }
            }
        }
        if ($preActivity && ($conditions['has_third_party_services'] ?? false)) {
            if (! $version->contracts) {
                $issues[] = 'Record effective third-party contracts before commencement.';
            }
            foreach ($version->contracts ?? [] as $contract) {
                $doc = Document::with('documentType')->find($contract['document_id'] ?? 0);
                if (! $doc || $doc->institution_id !== $version->activity->institution_id || $doc->scan_status !== 'clean' || ! $doc->is_signed ||
                    $doc->documentType->code !== 'third_party_contract' || empty($contract['effective_from']) ||
                    CarbonImmutable::parse($contract['effective_from'], config('lakbay.timezone'))->utc()->gt($version->starts_at) ||
                    (! empty($contract['effective_until']) && CarbonImmutable::parse($contract['effective_until'], config('lakbay.timezone'))->endOfDay()->utc()->lt($version->ends_at))) {
                    $issues[] = 'A third-party contract is missing, quarantined, unsigned or outside the activity period.';
                }
            }
        }

        return $issues;
    }
}
