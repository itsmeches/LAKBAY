<?php

namespace App\Http\Controllers;

use App\Models\AcknowledgmentDraft;
use App\Models\Submission;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\AcknowledgmentBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AcknowledgmentController extends Controller
{
    public function show(Request $request, string $publicId): Response
    {
        $submission = Submission::where('public_id', $publicId)->firstOrFail();
        $issued = $request->boolean('issued');
        app(Access::class)->authorize($request->user(), $submission->institution_id,
            $issued ? 'records.view' : 'acknowledgments.issue', false, ! $issued);
        if ($issued) {
            $data = $submission->acknowledgment?->summary['annex_k'] ?? null;
        } else {
            abort_unless($submission->status === 'ready_for_acknowledgment', 409, 'Only complete, unacknowledged submissions can be prepared.');
            app(AcknowledgmentBuilder::class)->verifyOriginals($request->user(), $submission);
            $data = AcknowledgmentDraft::where('submission_id', $submission->id)->first()?->payload;
        }
        abort_unless($data && ($data['renderer_version'] ?? null) === 1, 409, 'No preserved Annex K preparation exists. Historical letters remain available as signed originals.');
        $download = $request->boolean('download');
        $values = ['letter' => $data, 'pdf' => $download, 'issued' => $issued,
            'downloadUrl' => route('acknowledgment.print', ['publicId' => $publicId, 'issued' => (int) $issued, 'download' => 1])];
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if (! $download) {
            return response()->view('documents.acknowledgment', $values, 200, $headers + [
                'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'",
            ]);
        }
        $pdf = Pdf::loadView('documents.acknowledgment', $values)->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(475, 823, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica'), 7);
        Audit::record($request->user(), 'annex_k.generated', $submission, ['draft_revision' => $data['draft_revision'], 'signed' => false, 'issued_snapshot' => $issued]);

        return response($pdf->output(), 200, $headers + ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Acknowledgment-'.$submission->id.'.pdf"']);
    }
}
