<?php

namespace App\Actions\Submissions;

use App\Jobs\NotifyInstitution;
use App\Models\Acknowledgment;
use App\Models\Document;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IssueAcknowledgment
{
    public function execute(User $actor, Submission $submission, array $data): Acknowledgment
    {
        app(Access::class)->authorize($actor, $submission->institution_id, 'acknowledgments.issue', false, true);
        Validator::make($data, ['document_id' => 'required|integer', 'reference' => 'required|string|max:255', 'recipient' => 'required|string|max:255', 'signatory' => 'required|string|max:255', 'letter_date' => 'required|date'])->validate();

        return DB::transaction(function () use ($actor, $submission, $data) {
            $s = Submission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($s->status !== 'ready_for_acknowledgment' || $s->acknowledgment()->exists()) {
                throw ValidationException::withMessages(['submission' => 'A complete, unacknowledged review is required.']);
            }
            $doc = Document::findOrFail($data['document_id']);
            abort_unless($doc->institution_id === $s->institution_id, 403);
            if ($doc->documentType->code !== 'acknowledgment' || ! $doc->is_signed || $doc->scan_status !== 'clean') {
                throw ValidationException::withMessages(['document' => 'Upload a clean signed acknowledgment.']);
            }
            $a = Acknowledgment::create(collect($data)->only(['document_id', 'reference', 'recipient', 'signatory', 'letter_date'])->all() + ['submission_id' => $s->id, 'issued_by' => $actor->id, 'summary' => ['activities' => count($s->snapshot['activities']), 'student_participations' => collect($s->snapshot['activities'])->sum('student_count'), 'disclaimer' => 'Receipt and completeness review only. This is not CHED approval, endorsement, certification, or authorization.']]);
            $s->update(['status' => 'acknowledged']);
            Audit::record($actor, 'acknowledgment.issued', $s, ['acknowledgment_id' => $a->id]);
            DB::afterCommit(fn () => NotifyInstitution::dispatch($s->institution_id, 'ack-'.$a->id, 'Acknowledgment available', $s->reference.' acknowledgment is ready.'));

            return $a;
        });
    }
}
