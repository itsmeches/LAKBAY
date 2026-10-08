<?php

namespace App\Services\Reporting;

use App\Models\CompletionReport;
use App\Models\IncidentReport;
use App\Models\MonitoringRecord;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use Carbon\CarbonImmutable;

class RegionalReportBuilder
{
    public function build(User $actor, ?string $cutoff = null, ?string $periodFrom = null): array
    {
        abort_unless($actor->is_active && $actor->hasVerifiedEmail() && $actor->can('reports.export') && $actor->region_id, 403);
        $cutoff = $cutoff ? CarbonImmutable::parse($cutoff, config('lakbay.timezone'))->utc() : now();
        $from = $periodFrom ? CarbonImmutable::parse($periodFrom, config('lakbay.timezone'))->utc() : null;
        abort_if($from && $from->gt($cutoff), 422, 'Reporting period must begin before its cutoff.');
        $rows = collect();
        $submissions = app(Access::class)->scope(Submission::query(), $actor)->where('submitted_at', '<=', $cutoff)->orderBy('submitted_at')->orderBy('id')->get();
        foreach ($submissions as $s) {
            foreach ($s->snapshot['activities'] as $v) {
                $assessment = $v['classification_assessment'] ?? null;
                $rows->put($v['activity_id'], ['institution_id' => $s->institution_id, 'activity_id' => $v['activity_id'], 'title' => $v['title'], 'category' => $v['category'], 'requirement_category' => $v['requirement_category'], 'starts_at' => $v['starts_at'], 'student_participations' => $v['student_count'], 'reference' => $s->reference, 'status' => $s->status, 'is_late' => $s->is_late,
                    'classification_assessment' => $assessment]);
            }
        }
        if ($from) {
            $rows = $rows->filter(fn ($row) => CarbonImmutable::parse($row['starts_at'])->betweenIncluded($from, $cutoff));
        }
        $monitoring = app(Access::class)->scope(MonitoringRecord::query(), $actor)->with('items')->where('created_at', '<=', $cutoff)
            ->when($from, fn ($q) => $q->whereBetween('monitored_on', [$from->timezone(config('lakbay.timezone'))->toDateString(), $cutoff->timezone(config('lakbay.timezone'))->toDateString()]))->get()
            ->map(fn ($record) => ['id' => $record->id, 'institution_id' => $record->institution_id, 'period' => $record->period,
                'monitored_on' => $record->monitored_on->toDateString(), 'overall_rating' => $record->overall_rating,
                'criteria_version' => $record->criteria_version, 'items' => $record->items->map(fn ($item) => $item->only(['criterion_code', 'criterion', 'compliance', 'verification', 'remarks']))->all()])->all();

        $incidents = app(Access::class)->scope(IncidentReport::query(), $actor)->where('created_at', '<=', $cutoff)->whereNull('supplements_report_id')
            ->when($from, fn ($q) => $q->whereBetween('occurred_at', [$from, $cutoff]))->count();
        $completed = app(Access::class)->scope(CompletionReport::query(), $actor)->where('created_at', '<=', $cutoff)
            ->when($from, fn ($q) => $q->whereBetween('ended_at', [$from, $cutoff]))->distinct()->count('activity_id');

        return ['cutoff_at' => $cutoff->toIso8601String(), 'period_from' => $from?->toIso8601String(), 'totals' => ['activities' => $rows->count(), 'completed_activities' => $completed, 'estimated_student_participations' => $rows->sum('student_participations'), 'late_activity_submissions' => $rows->where('is_late', true)->count(), 'incidents' => $incidents, 'monitoring_records' => count($monitoring)], 'activities' => $rows->values()->all(), 'monitoring' => $monitoring];
    }
}
