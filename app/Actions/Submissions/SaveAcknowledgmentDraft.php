<?php

namespace App\Actions\Submissions;

use App\Models\AcknowledgmentDraft;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\AcknowledgmentBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveAcknowledgmentDraft
{
    /** @param array<string, mixed> $data */
    public function execute(User $actor, Submission $submission, array $data): AcknowledgmentDraft
    {
        app(Access::class)->authorize($actor, $submission->institution_id, 'acknowledgments.issue', false, true);
        $data = Validator::make($data, [
            'reference' => 'required|string|max:255',
            'recipient' => 'required|string|max:255',
            'recipient_position' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'salutation' => 'required|string|max:255',
            'signatory' => 'required|string|max:255',
            'letter_date' => 'required|date_format:Y-m-d',
            'letterhead_text' => 'required|string|max:1000',
            'originals_checked' => 'required|accepted',
        ])->validate();

        return DB::transaction(function () use ($actor, $submission, $data): AcknowledgmentDraft {
            $submission = Submission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($submission->type !== 'calendar' || $submission->status !== 'ready_for_acknowledgment' || $submission->acknowledgment()->exists()) {
                throw ValidationException::withMessages(['submission' => 'Annex K requires a complete, unacknowledged CLOCA calendar submission.']);
            }
            $builder = app(AcknowledgmentBuilder::class);
            $builder->verifyOriginals($actor, $submission);
            $draft = AcknowledgmentDraft::firstOrNew(['submission_id' => $submission->id]);
            $draft->revision = $draft->exists ? $draft->revision + 1 : 1;
            $draft->prepared_by = $actor->id;
            $draft->payload = $builder->snapshot($submission, $data, $actor, $draft->revision);
            $draft->save();
            Audit::record($actor, 'annex_k.prepared', $submission, ['draft_id' => $draft->id, 'revision' => $draft->revision]);

            return $draft;
        });
    }
}
