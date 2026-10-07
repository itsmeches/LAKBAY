<?php

namespace App\Actions\Submissions;

use App\Models\Calendar;
use App\Models\Submission;
use App\Models\User;

class SubmitCalendar
{
    public function execute(User $actor, Calendar $calendar, int $policyId, array $evidence, ?int $previousId = null, ?int $saaDraftId = null, ?int $certificationDraftId = null): Submission
    {
        return app(SubmitActivity::class)->execute($actor, $calendar->activities()->pluck('id')->all(), $policyId, $evidence, $calendar->id, $previousId, saaDraftId: $saaDraftId, certificationDraftId: $certificationDraftId);
    }
}
