<?php

namespace App\Http\Controllers;

use App\Models\ChangeReport;
use App\Models\ChangeReportDraft;
use App\Services\Audit;
use App\Services\Reporting\ChangeReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChangeReportController extends Controller
{
    public function draft(Request $request, ChangeReportDraft $draft, ChangeReportBuilder $builder): Response
    {
        $builder->authorize($request->user(), $draft->institution_id);

        return $this->render($request, $builder, $builder->snapshot($draft), $draft, route('change.draft', ['draft' => $draft->id, 'download' => 1]));
    }

    public function report(Request $request, ChangeReport $report, ChangeReportBuilder $builder): Response
    {
        $builder->authorize($request->user(), $report->institution_id);
        abort_unless(! empty($report->annex_i), 409, 'This historical report has no frozen Annex I preparation data. Retrieve its original signed report from the Document library.');

        return $this->render($request, $builder, $report->annex_i, $report, route('change.report', ['report' => $report->id, 'download' => 1]));
    }

    /** @param array<string, mixed> $data */
    private function render(Request $request, ChangeReportBuilder $builder, array $data, ChangeReportDraft|ChangeReport $record, string $downloadUrl): Response
    {
        abort_unless(($data['renderer_version'] ?? null) === 1, 409, 'This Annex I rendering version is not supported.');
        $missing = $builder->missingFields($data);
        $download = $request->boolean('download');
        abort_if($download && $missing !== [], 422, 'Complete change print fields before downloading: '.implode('; ', $missing));
        $values = ['change' => $data, 'missing' => $missing, 'letterhead' => $builder->letterhead($request->user(), $data), 'pdf' => $download,
            'downloadUrl' => $downloadUrl, 'preserved' => $record instanceof ChangeReport];
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if (! $download) {
            return response()->view('documents.change', $values, 200, $headers + [
                'Content-Security-Policy' => "default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'",
            ]);
        }
        $pdf = Pdf::loadView('documents.change', $values)->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(475, 823, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica'), 7);
        Audit::record($request->user(), 'annex_i.generated', $record, ['template' => $data['template'], 'draft_revision' => $data['draft_revision'], 'signed' => false]);

        return response($pdf->output(), 200, $headers + ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="change-'.$record->id.'.pdf"']);
    }
}
