<?php

namespace App\Http\Controllers;

use App\Models\AccountabilityAffidavit;
use App\Models\Submission;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\SaaBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SaaController extends Controller
{
    public function draft(Request $request, AccountabilityAffidavit $affidavit, SaaBuilder $builder): Response
    {
        app(Access::class)->authorize($request->user(), $affidavit->institution_id, 'records.view');

        return $this->render($request, $builder, $builder->snapshot($affidavit), $affidavit,
            route('saa.draft', ['affidavit' => $affidavit->id, 'download' => 1]), null);
    }

    public function submission(Request $request, string $publicId, SaaBuilder $builder): Response
    {
        $submission = Submission::where('public_id', $publicId)->firstOrFail();
        app(Access::class)->authorize($request->user(), $submission->institution_id, 'records.view');
        abort_unless(! empty($submission->snapshot['annex_c']), 409,
            'This submission has no frozen Annex C draft. Retrieve its original signed SAA from the Document library.');

        return $this->render($request, $builder, $submission->snapshot['annex_c'], $submission,
            route('saa.submission', ['publicId' => $publicId, 'download' => 1]), $submission->reference);
    }

    /** @param array<string, mixed> $data */
    private function render(Request $request, SaaBuilder $builder, array $data, AccountabilityAffidavit|Submission $record, string $downloadUrl, ?string $reference): Response
    {
        abort_unless(($data['renderer_version'] ?? null) === 1, 409, 'This Annex C rendering version is not supported.');
        $missing = $builder->missingFields($data);
        $download = $request->boolean('download');
        abort_if($download && $missing !== [], 422, 'Complete SAA print fields before downloading: '.implode('; ', $missing));
        $values = ['saa' => $data, 'missing' => $missing, 'letterhead' => $builder->letterhead($request->user(), $data),
            'pdf' => $download, 'downloadUrl' => $downloadUrl, 'reference' => $reference];
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if (! $download) {
            return response()->view('documents.saa', $values, 200, $headers + [
                'Content-Security-Policy' => "default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'",
            ]);
        }
        $pdf = Pdf::loadView('documents.saa', $values)->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(475, 823, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica'), 7);
        Audit::record($request->user(), 'annex_c.generated', $record, ['template' => $data['template'],
            'draft_revision' => $data['draft_revision'], 'signed' => false, 'notarized' => false]);

        return response($pdf->output(), 200, $headers + ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="SAA-'.($reference ?? $record->id).'.pdf"']);
    }
}
