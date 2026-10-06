<?php

namespace App\Jobs;

use App\Models\Export;
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
        $data = app(RegionalReportBuilder::class)->build($actor, $e->filters['cutoff_at'] ?? null);
        $path = 'exports/'.$e->id.'.'.($e->filters['format'] ?? 'csv');
        if (($e->filters['format'] ?? 'csv') === 'pdf') {
            $contents = Pdf::loadView('documents.regional-report', ['report' => $data])->setPaper('a4', 'landscape')->output();
        } else {
            $stream = fopen('php://temp', 'r+');
            fputcsv($stream, ['Institution', 'Activity', 'Category', 'Requirements category', 'Starts', 'Estimated student participations', 'Submission', 'Status', 'Late'], ',', '"', '');
            foreach ($data['activities'] as $r) {
                $values = [$r['institution_id'], $r['title'], $r['category'], $r['requirement_category'], $r['starts_at'], $r['student_participations'], $r['reference'], $r['status'], $r['is_late'] ? 'Yes' : 'No'];
                $values = array_map(fn ($v) => is_string($v) && preg_match('/^[=+@\-\t\r]/', $v) ? "'".$v : $v, $values);
                fputcsv($stream, $values, ',', '"', '');
            }rewind($stream);
            $contents = stream_get_contents($stream);
            fclose($stream);
        }
        Storage::disk('private')->put($path, $contents);
        $e->update(['status' => 'ready', 'path' => $path, 'expires_at' => now()->addDay()]);
    }
}
