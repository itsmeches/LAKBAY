<?php

namespace App\Services\Reporting;

use App\Models\AccountabilityAffidavit;
use App\Models\Document;
use App\Models\User;

class SaaBuilder
{
    /** @return array<string, mixed> */
    public function snapshot(AccountabilityAffidavit $draft): array
    {
        $draft->loadMissing(['institution', 'academicPeriod', 'formTemplate']);
        $details = array_map(static fn ($value) => is_string($value) && blank($value) ? null : $value, $draft->details ?? []);
        foreach (['office_address' => $draft->institution->address, 'semester' => $draft->academicPeriod->term,
            'academic_year' => $draft->academicPeriod->label] as $key => $fallback) {
            $details[$key] = ($details[$key] ?? null) ?: $fallback;
        }
        $image = ! empty($details['letterhead_document_id']) ? Document::find($details['letterhead_document_id']) : null;

        return [
            'renderer_version' => 1, 'draft_id' => $draft->id, 'draft_revision' => $draft->revision,
            'academic_period_id' => $draft->academic_period_id,
            'template' => ['id' => $draft->formTemplate->id, 'version' => $draft->formTemplate->version,
                'policy_version_id' => $draft->formTemplate->policy_version_id, 'annex' => 'C', 'source_page' => 22],
            'header' => ['institution_id' => $draft->institution_id, 'hei_name' => $draft->institution->name,
                'letterhead_text' => ($details['letterhead_text'] ?? null) ?: $draft->institution->name."\n".$draft->institution->address,
                'letterhead' => $image ? ['id' => $image->id, 'sha256' => $image->sha256] : null],
            'details' => $details,
        ];
    }

    /** @param array<string, mixed> $data
     * @return list<string>
     */
    public function missingFields(array $data): array
    {
        $missing = [];
        foreach (['name' => 'Affiant / HEI head name', 'position' => 'Affiant position',
            'office_address' => 'Office address', 'citizenship' => 'Citizenship',
            'semester' => 'Semester coverage', 'academic_year' => 'Academic year'] as $key => $label) {
            if (blank($data['details'][$key] ?? null)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /** @param array<string, mixed> $data */
    public function letterhead(User $actor, array $data): ?string
    {
        return app(ClocaBuilder::class)->letterhead($actor, $data);
    }
}
