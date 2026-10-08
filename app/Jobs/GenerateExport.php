<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\RegionalReport;
use App\Models\User;
use App\Services\Reporting\RegionalReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateExport implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $exportId) {}

    public function handle(): void
    {
        $e = Export::findOrFail($this->exportId);
        $actor = User::findOrFail($e->requested_by);
        if (! empty($e->filters['regional_report_id'])) {
            $report = RegionalReport::findOrFail($e->filters['regional_report_id']);
            abort_unless($actor->is_active && $actor->hasVerifiedEmail() && $actor->can('panel.ched') && $actor->can('reports.export') && $actor->region_id === $report->region_id, 403);
            $data = ['cutoff_at' => $report->cutoff_at->toIso8601String(), 'period_from' => $report->filters['period_from'] ?? null,
                'totals' => $report->totals, 'activities' => $report->filters['activities'], 'monitoring' => $report->filters['monitoring'] ?? []];
        } else {
            $data = app(RegionalReportBuilder::class)->build($actor, $e->filters['cutoff_at'] ?? null);
        }
        $path = 'exports/'.$e->id.'.'.($e->filters['format'] ?? 'csv');
        if (($e->filters['format'] ?? 'csv') === 'pdf') {
            $contents = Pdf::loadView('documents.regional-report', ['report' => $data])->setPaper('a4', 'landscape')->output();
        } else {
            $stream = fopen('php://temp', 'r+');
            fputcsv($stream, ['Institution', 'Activity', 'Category', 'Requirements category', 'Starts', 'Estimated student participations', 'Submission', 'Status', 'Late', 'Assessment', 'Assessment state', 'Classification rules', 'Baseline receipt', 'Confirmed by', 'Change reason', 'Immediate RCD notice', 'RCD anchor'], ',', '"', '');
            foreach ($data['activities'] as $r) {
                $values = [$r['institution_id'], $r['title'], $r['category'], $r['requirement_category'], $r['starts_at'], $r['student_participations'], $r['reference'], $r['status'], $r['is_late'] ? 'Yes' : 'No'];
                $assessment = $r['classification_assessment'] ?? [];
                $values = [...$values, $assessment['id'] ?? '', $assessment['state'] ?? 'Historical / not snapshotted', $assessment['classification_rule_set_id'] ?? '', $assessment['baseline_submission_id'] ?? '', $assessment['confirmed_by'] ?? '', $assessment['reason'] ?? '', isset($assessment['timing']) ? (! empty($assessment['timing']['immediate_notice_required']) ? 'Yes' : 'No') : '', $assessment['timing']['rcd_anchor'] ?? ''];
                $values = array_map(fn ($v) => is_string($v) && preg_match('/^[=+@\-\t\r]/', $v) ? "'".$v : $v, $values);
                fputcsv($stream, $values, ',', '"', '');
            }
            if (! empty($e->filters['regional_report_id'])) {
                fputcsv($stream, ['Summary metric', 'Value'], ',', '"', '');
                foreach ($data['totals'] as $metric => $value) {
                    fputcsv($stream, [$metric, $value], ',', '"', '');
                }
                fputcsv($stream, ['Institution', 'Monitoring date', 'Criterion', 'Compliance', 'Verification', 'Remarks'], ',', '"', '');
                foreach ($data['monitoring'] as $record) {
                    foreach ($record['items'] as $item) {
                        $values = [$record['institution_id'], $record['monitored_on'], $item['criterion'], $item['compliance'], $item['verification'], $item['remarks']];
                        $values = array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value, $values);
                        fputcsv($stream, $values, ',', '"', '');
                    }
                }
            }
            rewind($stream);
            $contents = stream_get_contents($stream);
            fclose($stream);
        }
        Storage::disk('private')->put($path, $contents);
        $e->update(['status' => 'ready', 'path' => $path, 'expires_at' => now()->addDay()]);
    }
}
