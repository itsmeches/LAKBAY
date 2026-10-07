<?php

namespace App\Services\Reporting;

use App\Models\CertificationDraft;
use App\Models\Document;
use App\Models\User;

class CertificationBuilder
{
    /** @return array<string, mixed> */
    public function snapshot(CertificationDraft $draft): array
    {
        $draft->loadMissing(['institution', 'academicPeriod', 'formTemplate']);
        $details = array_map(static fn ($value) => is_string($value) && blank($value) ? null : $value, $draft->details ?? []);
        $image = ! empty($details['letterhead_document_id']) ? Document::find($details['letterhead_document_id']) : null;

        return [
            'renderer_version' => 1, 'draft_id' => $draft->id, 'draft_revision' => $draft->revision,
            'academic_period_id' => $draft->academic_period_id,
            'template' => ['id' => $draft->formTemplate->id, 'version' => $draft->formTemplate->version,
                'policy_version_id' => $draft->formTemplate->policy_version_id, 'annex' => 'F', 'source_pages' => [25, 26]],
            'header' => ['institution_id' => $draft->institution_id, 'hei_name' => $draft->institution->name, 'address' => $draft->institution->address,
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
        foreach (['official' => 'Authorized HEI official', 'position' => 'Designation / position'] as $key => $label) {
            if (blank($data['details'][$key] ?? null)) {
                $missing[] = $label;
            }
        }
        if (blank($data['header']['address'])) {
            $missing[] = 'HEI profile address';
        }
        foreach ($data['details']['definition']['rows'] as $code => $row) {
            $item = $data['details']['items'][$code] ?? [];
            if (($item['status'] ?? null) === 'not_applicable') {
                if (blank($item['reason'] ?? null)) {
                    $missing[] = $row['requirement'].': applicability reason';
                }

                continue;
            }
            if (($item['status'] ?? null) !== 'attested') {
                $missing[] = $row['requirement'].': attestation pending';

                continue;
            }
            foreach ($row['supporting'] as $key => $label) {
                if (empty($item['supporting'][$key])) {
                    $missing[] = $label.': availability not attested';
                }
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
