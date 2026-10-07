<?php

namespace App\Services\Compliance;

use App\Models\Activity;
use App\Models\Calendar;
use App\Models\CaseRecord;
use App\Models\ClearanceRequest;
use App\Models\InstitutionalRestriction;
use App\Models\Submission;

class ActivityReadiness
{
    public function summary(Calendar|Activity $record): array
    {
        if ($record instanceof Activity) {
            return $this->issues($record);
        }
        $issues = [];
        $count = 0;
        foreach ($record->activities()->cursor() as $activity) {
            $count++;
            foreach ($this->issues($activity) as $issue) {
                $issues[] = $activity->title.': '.$issue;
            }
        }

        return $count ? $issues : ['Calendar has no activities.'];
    }

    public function issues(Activity $activity): array
    {
        $v = $activity->latestVersion;
        if (! $v) {
            return ['No activity details recorded.'];
        }$issues = [];
        if (empty($v->authorization['official']) || empty($v->authorization['date'])) {
            $issues[] = 'HEI authorization evidence missing.';
        }
        $policy = app(PolicyRules::class)->forActivity($activity);
        if (PolicyRules::isCmo($policy)) {
            $issues = array_merge($issues, app(ActivityCompliance::class)->issues($v));
            $preActivity = Submission::whereHas('activityVersions', fn ($q) => $q->where('activity_versions.id', $v->id))
                ->where('stage', 'pre_activity')->whereNotIn('status', ['superseded', 'withdrawn'])->first();
            if (! $preActivity) {
                $issues[] = 'Pre-activity documentary package has not been received for the current revision.';
            }
            foreach (['inclusivity', 'parallel_learning', 'grievance_mechanism', 'student_participation', 'emergency_preparedness'] as $item) {
                if (empty(trim($v->preparation[$item] ?? ''))) {
                    $issues[] = 'Record the '.$item.' preparation/evidence reference.';
                }
            }
        }
        $restrictions = InstitutionalRestriction::where('institution_id', $activity->institution_id)->where(fn ($q) => $q->whereNull('activity_id')->orWhere('activity_id', $activity->id))->where('effective_from', '<=', today(config('lakbay.timezone')))->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', today(config('lakbay.timezone'))))->get();
        if ($restrictions->contains('type', 'suspension')) {
            $issues[] = 'An active signed suspension applies.';
        }
        $pending = CaseRecord::where('institution_id', $activity->institution_id)->where('type', 'investigation')->where('status', 'investigating')->exists();
        $cleared = ClearanceRequest::where('activity_version_id', $v->id)->where(function ($q) {
            $q->where(function ($q) {
                $q->where('decision', 'granted')->whereDoesntHave('reconsideration', fn ($q) => $q->where('decision', 'denied'));
            })->orWhereHas('reconsideration', fn ($q) => $q->where('decision', 'granted'));
        })->exists();
        if (($pending || $restrictions->contains('type', 'clearance_required')) && ! $cleared) {
            $issues[] = 'Activity-specific CEB clearance required for the current revision.';
        }

        return $issues;
    }
}
