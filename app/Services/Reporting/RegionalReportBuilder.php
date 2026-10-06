<?php

namespace App\Services\Reporting;

use App\Models\IncidentReport;
use App\Models\MonitoringRecord;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use Carbon\CarbonImmutable;

class RegionalReportBuilder
{
    public function build(User $actor, ?string $cutoff = null): array
    {
        abort_unless($actor->is_active && $actor->hasVerifiedEmail() && $actor->can('reports.export') && $actor->region_id, 403);
        $cutoff = $cutoff ? CarbonImmutable::parse($cutoff, config('lakbay.timezone'))->utc() : now();
        $rows = collect();
        $submissions = app(Access::class)->scope(Submission::query(), $actor)->where('submitted_at', '<=', $cutoff)->orderBy('submitted_at')->orderBy('id')->get();
        foreach ($submissions as $s) {
            foreach ($s->snapshot['activities'] as $v) {
                $rows->put($v['activity_id'], ['institution_id' => $s->institution_id, 'activity_id' => $v['activity_id'], 'title' => $v['title'], 'category' => $v['category'], 'requirement_category' => $v['requirement_category'], 'starts_at' => $v['starts_at'], 'student_participations' => $v['student_count'], 'reference' => $s->reference, 'status' => $s->status, 'is_late' => $s->is_late]);
            }
        }

        return ['cutoff_at' => $cutoff->toIso8601String(), 'totals' => ['activities' => $rows->count(), 'estimated_student_participations' => $rows->sum('student_participations'), 'late_activity_submissions' => $rows->where('is_late', true)->count(), 'incidents' => app(Access::class)->scope(IncidentReport::query(), $actor)->where('created_at', '<=', $cutoff)->count(), 'monitoring_records' => app(Access::class)->scope(MonitoringRecord::query(), $actor)->where('created_at', '<=', $cutoff)->count()], 'activities' => $rows->values()->all()];
    }
}
