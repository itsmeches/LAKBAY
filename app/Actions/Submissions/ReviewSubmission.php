<?php

namespace App\Actions\Submissions;

use App\Jobs\NotifyInstitution;
use App\Models\ReviewFinding;
use App\Models\Submission;
use App\Models\SubmissionReview;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewSubmission
{
    public function execute(User $actor, Submission $submission, string $outcome, string $remarks): SubmissionReview
    {
        app(Access::class)->authorize($actor, $submission->institution_id, 'submissions.review', false, true);
        if (! in_array($outcome, ['corrections_requested', 'complete'], true) || ! trim($remarks)) {
            throw ValidationException::withMessages(['review' => 'Choose an outcome and enter review remarks.']);
        }

        return DB::transaction(function () use ($actor, $submission, $outcome, $remarks) {
            $s = Submission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if (! in_array($s->status, ['submitted', 'under_review'], true)) {
                throw ValidationException::withMessages(['submission' => 'This revision has already been reviewed.']);
            }
            abort_unless(! $s->assigned_to || $s->assigned_to === $actor->id, 403);
            $r = SubmissionReview::create(['submission_id' => $s->id, 'reviewed_by' => $actor->id, 'outcome' => $outcome, 'remarks' => $remarks]);
            if ($outcome === 'corrections_requested') {
                ReviewFinding::create(['submission_review_id' => $r->id, 'deficiency' => $remarks, 'requested_action' => $remarks]);
            }
            $s->update(['status' => $outcome === 'complete' ? 'ready_for_acknowledgment' : 'corrections_requested', 'assigned_to' => $actor->id]);
            Audit::record($actor, 'submission.reviewed', $s, ['outcome' => $outcome]);
            DB::afterCommit(fn () => NotifyInstitution::dispatch($s->institution_id, 'review-'.$r->id, 'Submission review updated', $s->reference.' has a new review. Sign in to read it.'));

            return $r;
        });
    }
}
