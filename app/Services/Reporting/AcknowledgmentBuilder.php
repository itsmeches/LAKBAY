<?php

namespace App\Services\Reporting;

use App\Models\Document;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AcknowledgmentBuilder
{
    public function verifyOriginals(User $actor, Submission $submission): void
    {
        $documents = collect($submission->snapshot['documents'] ?? []);
        $attached = collect($submission->snapshot['evidence'] ?? [])->where('evidence_status', 'submitted')->pluck('document_ids')->flatten()->all();
        foreach (['cloca', 'saa'] as $code) {
            $original = $documents->first(function (array $snapshot) use ($code, $attached): bool {
                return in_array($snapshot['id'], $attached, true) && Document::whereKey($snapshot['id'])
                    ->whereHas('documentType', fn ($query) => $query->where('code', $code))->exists();
            });
            $document = $original ? Document::find($original['id']) : null;
            if (! $document || $document->institution_id !== $submission->institution_id || ! app(Access::class)->document($actor, $document)
                || ! $document->is_signed || $document->mime !== 'application/pdf' || $document->scan_status !== 'clean') {
                throw ValidationException::withMessages(['originals_checked' => 'The exact submission must include clean signed CLOCA and SAA PDFs. CHED must inspect signing and notarization.']);
            }
            $disk = Storage::disk($document->disk);
            if (! $disk->exists($document->path) || ! hash_equals($original['sha256'], $document->sha256)
                || ! hash_equals($document->sha256, hash('sha256', $disk->get($document->path)))) {
                throw ValidationException::withMessages(['originals_checked' => 'Submitted original evidence is missing or does not match its frozen hash.']);
            }
        }
    }

    /** @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function snapshot(Submission $submission, array $fields, User $actor, int $revision): array
    {
        $activities = collect($submission->snapshot['activities'] ?? []);
        $groups = $activities->groupBy(fn ($activity) => filled($activity['program'] ?? null) ? $activity['program'] : 'Institution-wide / unspecified')
            ->map(fn ($rows, $program) => [
                'program_course' => $program, 'period_from' => $rows->min('starts_at'), 'period_until' => $rows->max('ends_at'),
                'activity_count' => $rows->count(), 'student_participations' => $rows->sum('student_count'),
            ])->values()->all();

        return [
            'renderer_version' => 1,
            'template' => ['annex' => 'K', 'source' => 'CMO No. 24, s. 2026', 'source_page' => 31, 'version' => 1],
            'submission_id' => $submission->id, 'submission_reference' => $submission->reference,
            'submission_revision' => $submission->revision, 'policy_version_id' => $submission->policy_version_id,
            'received_at' => $submission->received_at->toIso8601String(),
            'hei_name' => data_get($submission->snapshot, 'annex_b.header.hei_name') ?: $submission->institution->name,
            'fields' => $fields, 'programs' => $groups, 'draft_revision' => $revision,
            'originals_check' => ['by' => $actor->id, 'at' => now()->toIso8601String(), 'attestation' => 'Inspected duly signed CLOCA and duly notarized SAA originals.'],
            'student_count_basis' => 'Sum of planned student participations; not deduplicated individuals.',
        ];
    }
}
