<?php

namespace App\Http\Controllers;

use App\Models\MonitoringRecord;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\MonitoringBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MonitoringController extends Controller
{
    public function show(Request $request, MonitoringRecord $record, MonitoringBuilder $builder): Response
    {
        app(Access::class)->authorize($request->user(), $record->institution_id, 'records.view', false, true);
        $data = $builder->preserved($record);
        $download = $request->boolean('download');
        $values = ['monitoring' => $data, 'pdf' => $download,
            'firstItems' => $builder->printableItems($data, 0, 10), 'lastItems' => $builder->printableItems($data, 10, 14),
            'downloadUrl' => route('monitoring.print', ['record' => $record, 'download' => 1])];
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if (! $download) {
            return response()->view('documents.monitoring', $values, 200, $headers + [
                'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'",
            ]);
        }
        $pdf = Pdf::loadView('documents.monitoring', $values)->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(475, 823, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica'), 7);
        Audit::record($request->user(), 'annex_m.generated', $record, ['template' => $data['template'], 'signed' => false]);

        return response($pdf->output(), 200, $headers + ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Monitoring-'.$record->id.'.pdf"']);
    }
}
