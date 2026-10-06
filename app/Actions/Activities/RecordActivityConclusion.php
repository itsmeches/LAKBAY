<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecordActivityConclusion
{
    public function execute(User $actor, Activity $activity, string $endedAt): void
    {
        app(Access::class)->authorize($actor, $activity->institution_id, 'reports.submit', true);
        Validator::make(['ended_at' => $endedAt], ['ended_at' => 'required|date'])->validate();
        $ended = CarbonImmutable::parse($endedAt, config('lakbay.timezone'))->utc();
        abort_if($ended->isFuture(), 422, 'Actual conclusion cannot be in the future.');
        DB::transaction(function () use ($actor, $activity, $ended) {
            $a = Activity::whereKey($activity->id)->lockForUpdate()->firstOrFail();
            abort_if($a->actual_ended_at !== null, 409);
            $a->update(['actual_ended_at' => $ended, 'execution_status' => 'completed']);
            Audit::record($actor, 'activity.concluded', $a);
        });
    }
}
