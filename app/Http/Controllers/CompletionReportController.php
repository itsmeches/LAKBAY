<?php

namespace App\Http\Controllers;

use App\Models\CompletionReport;
use App\Models\CompletionReportDraft;
use App\Services\Audit;
use App\Services\Reporting\CompletionReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompletionReportController extends Controller
{
    public function draft(Request $request, CompletionReportDraft $draft, CompletionReportBuilder $builder): Response
    {
        $builder->authorize($request->user(), $draft->institution_id, (bool) ($draft->details['incidents_encountered'] ?? false));

        return $this->render($request, $builder, $builder->snapshot($draft), $draft, route('roc.draft', ['draft' => $draft->id, 'download' => 1]));
    }

    public function report(Request $request, CompletionReport $report, CompletionReportBuilder $builder): Response
    {
        $builder->authorize($request->user(), $report->institution_id, $report->incidents_encountered);
        abort_unless(! empty($report->annex_g), 409, 'This historical report has no frozen Annex G preparation data. Retrieve its original signed report from the Document library.');

        return $this->render($request, $builder, $report->annex_g, $report, route('roc.report', ['report' => $report->id, 'download' => 1]));
    }

    /** @param array<string, mixed> $data */
    private function render(Request $request, CompletionReportBuilder $builder, array $data, CompletionReportDraft|CompletionReport $record, string $downloadUrl): Response
    {
        abort_unless(($data['renderer_version'] ?? null) === 1, 409, 'This Annex G rendering version is not supported.');
        $missing = $builder->missingFields($data);
        $download = $request->boolean('download');
        abort_if($download && $missing !== [], 422, 'Complete ROC print fields before downloading: '.implode('; ', $missing));
        $values = ['roc' => $data, 'missing' => $missing, 'letterhead' => $builder->letterhead($request->user(), $data), 'pdf' => $download,
            'downloadUrl' => $downloadUrl, 'preserved' => $record instanceof CompletionReport];
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if (! $download) {
            return response()->view('documents.completion', $values, 200, $headers + [
                'Content-Security-Policy' => "default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'",
            ]);
        }
        $pdf = Pdf::loadView('documents.completion', $values)->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(475, 823, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica'), 7);
        Audit::record($request->user(), 'annex_g.generated', $record, ['template' => $data['template'], 'draft_revision' => $data['draft_revision'], 'signed' => false]);

        return response($pdf->output(), 200, $headers + ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="completion-'.$record->id.'.pdf"']);
    }
}
