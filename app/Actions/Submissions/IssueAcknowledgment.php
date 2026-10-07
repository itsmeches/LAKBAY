<?php

namespace App\Actions\Submissions;

use App\Jobs\NotifyInstitution;
use App\Models\Acknowledgment;
use App\Models\AcknowledgmentDraft;
use App\Models\Document;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\AcknowledgmentBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IssueAcknowledgment
{
    public function execute(User $actor, Submission $submission, array $data): Acknowledgment
    {
        app(Access::class)->authorize($actor, $submission->institution_id, 'acknowledgments.issue', false, true);
        Validator::make($data, ['document_id' => 'required|integer', 'reference' => 'required|string|max:255', 'recipient' => 'required|string|max:255', 'signatory' => 'required|string|max:255', 'letter_date' => 'required|date', 'use_annex_k' => 'sometimes|boolean'])->validate();

        return DB::transaction(function () use ($actor, $submission, $data) {
            $s = Submission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($s->status !== 'ready_for_acknowledgment' || $s->acknowledgment()->exists()) {
                throw ValidationException::withMessages(['submission' => 'A complete, unacknowledged review is required.']);
            }
            $annexK = null;
            if (! empty($data['use_annex_k'])) {
                $draft = AcknowledgmentDraft::where('submission_id', $s->id)->lockForUpdate()->first();
                if (! $draft || $s->type !== 'calendar') {
                    throw ValidationException::withMessages(['use_annex_k' => 'Prepare Annex K for this exact calendar revision first.']);
                }
                app(AcknowledgmentBuilder::class)->verifyOriginals($actor, $s);
                $annexK = $draft->payload;
                foreach (['reference', 'recipient', 'signatory', 'letter_date'] as $field) {
                    if ($data[$field] !== $annexK['fields'][$field]) {
                        throw ValidationException::withMessages([$field => 'Use the saved Annex K particulars, or revise the preparation before recording its signed original.']);
                    }
                }
            }
            $doc = Document::findOrFail($data['document_id']);
            abort_unless($doc->institution_id === $s->institution_id, 403);
            if ($doc->documentType->code !== 'acknowledgment' || ! $doc->is_signed || $doc->mime !== 'application/pdf' || $doc->scan_status !== 'clean' || ! app(Access::class)->document($actor, $doc)) {
                throw ValidationException::withMessages(['document' => 'Upload a clean signed acknowledgment.']);
            }
            if ($annexK !== null && (! Storage::disk($doc->disk)->exists($doc->path) || ! hash_equals($doc->sha256, hash('sha256', Storage::disk($doc->disk)->get($doc->path))))) {
                throw ValidationException::withMessages(['document' => 'The signed letter is missing or does not match its scanned hash.']);
            }
            $groups = collect($s->snapshot['activities'])->groupBy(fn ($v) => $v['program'] ?? 'Institution-wide / unspecified')->map(fn ($activities, $program) => [
                'program_course' => $program, 'period_from' => $activities->min('starts_at'), 'period_until' => $activities->max('ends_at'),
                'activity_count' => $activities->count(), 'student_participations' => $activities->sum('student_count'),
            ])->values()->all();
            $a = Acknowledgment::create(collect($data)->only(['document_id', 'reference', 'recipient', 'signatory', 'letter_date'])->all() + ['submission_id' => $s->id, 'issued_by' => $actor->id, 'summary' => ['received_at' => $s->received_at->toIso8601String(), 'stage' => $s->stage,
                'programs' => $groups, 'activities' => count($s->snapshot['activities']), 'student_participations' => collect($s->snapshot['activities'])->sum('student_count'), 'annex_k' => $annexK, 'disclaimer' => 'Receipt and completeness review only. This is not CHED approval, endorsement, certification, or authorization.']]);
            $s->update(['status' => 'acknowledged']);
            Audit::record($actor, 'acknowledgment.issued', $s, ['acknowledgment_id' => $a->id]);
            DB::afterCommit(fn () => NotifyInstitution::dispatch($s->institution_id, 'ack-'.$a->id, 'Acknowledgment available', $s->reference.' acknowledgment is ready.'));

            return $a;
        });
    }
}
