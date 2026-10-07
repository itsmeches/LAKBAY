<?php

namespace App\Http\Controllers;

use App\Models\CertificationDraft;
use App\Models\Submission;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\CertificationBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CertificationController extends Controller
{
    public function draft(Request $request, CertificationDraft $draft, CertificationBuilder $builder): Response
    {
        app(Access::class)->authorize($request->user(), $draft->institution_id, 'records.view');

        return $this->render($request, $builder, $builder->snapshot($draft), $draft,
            route('certification.draft', ['draft' => $draft->id, 'download' => 1]), null);
    }

    public function submission(Request $request, string $publicId, CertificationBuilder $builder): Response
    {
        $submission = Submission::where('public_id', $publicId)->firstOrFail();
        app(Access::class)->authorize($request->user(), $submission->institution_id, 'records.view');
        abort_unless(! empty($submission->snapshot['annex_f']), 409,
            'This submission has no frozen Annex F draft. Retrieve its original signed certification from the Document library.');

        return $this->render($request, $builder, $submission->snapshot['annex_f'], $submission,
            route('certification.submission', ['publicId' => $publicId, 'download' => 1]), $submission->reference);
    }

    /** @param array<string, mixed> $data */
    private function render(Request $request, CertificationBuilder $builder, array $data, CertificationDraft|Submission $record, string $downloadUrl, ?string $reference): Response
    {
        abort_unless(($data['renderer_version'] ?? null) === 1, 409, 'This Annex F rendering version is not supported.');
        $missing = $builder->missingFields($data);
        $download = $request->boolean('download');
        abort_if($download && $missing !== [], 422, 'Complete certification print fields before downloading: '.implode('; ', $missing));
        $values = ['certification' => $data, 'missing' => $missing, 'letterhead' => $builder->letterhead($request->user(), $data),
            'pdf' => $download, 'downloadUrl' => $downloadUrl, 'reference' => $reference];
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if (! $download) {
            return response()->view('documents.certification', $values, 200, $headers + [
                'Content-Security-Policy' => "default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'",
            ]);
        }
        $pdf = Pdf::loadView('documents.certification', $values)->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(475, 823, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica'), 7);
        Audit::record($request->user(), 'annex_f.generated', $record, ['template' => $data['template'],
            'draft_revision' => $data['draft_revision'], 'signed' => false]);

        return response($pdf->output(), 200, $headers + ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="certification-'.($reference ?? $record->id).'.pdf"']);
    }
}
