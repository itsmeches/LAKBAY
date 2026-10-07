<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityVersion;
use App\Models\Submission;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\DiagnosticBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DiagnosticController extends Controller
{
    public function activity(Request $request, Activity $activity, DiagnosticBuilder $builder): Response
    {
        app(Access::class)->authorize($request->user(), $activity->institution_id, 'records.view');
        $version = $activity->latestVersion;
        $data = $builder->snapshot($version);
        abort_unless($data, 409, 'Prepare the Annex D diagnostic form through Activity plans -> Revise first.');

        return $this->render($request, $builder, $data, $version,
            route('diagnostic.activity', ['activity' => $activity->id, 'download' => 1]), null);
    }

    public function submission(Request $request, string $publicId, DiagnosticBuilder $builder, ?int $version = null): Response
    {
        $submission = Submission::where('public_id', $publicId)->firstOrFail();
        app(Access::class)->authorize($request->user(), $submission->institution_id, 'records.view');
        $entries = $submission->snapshot['annex_d'] ?? [];
        abort_unless($entries, 409, 'No frozen Annex D form exists for this submission. Retrieve original diagnostic evidence instead.');
        if ($version === null) {
            return response()->view('documents.diagnostic', ['entries' => $entries, 'reference' => $submission->reference,
                'publicId' => $publicId], 200, $this->headers() + $this->previewHeaders());
        }
        abort_unless(isset($entries[$version]), 404);

        return $this->render($request, $builder, $entries[$version], $submission,
            route('diagnostic.submission', ['publicId' => $publicId, 'version' => $version, 'download' => 1]), $submission->reference);
    }

    /** @param array<string, mixed> $data */
    private function render(Request $request, DiagnosticBuilder $builder, array $data, ActivityVersion|Submission $record, string $downloadUrl, ?string $reference): Response
    {
        abort_unless(($data['renderer_version'] ?? null) === 1, 409, 'Unsupported Annex D rendering version.');
        $missing = $builder->missingFields($data);
        $download = $request->boolean('download');
        abort_if($download && $missing !== [], 422, 'Complete diagnostic print fields before downloading: '.implode('; ', $missing));
        $values = ['diagnostic' => $data, 'missing' => $missing, 'letterhead' => $builder->letterhead($request->user(), $data),
            'pdf' => $download, 'downloadUrl' => $downloadUrl, 'reference' => $reference];
        if (! $download) {
            return response()->view('documents.diagnostic', $values, 200, $this->headers() + $this->previewHeaders());
        }
        $pdf = Pdf::loadView('documents.diagnostic', $values)->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(475, 823, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica'), 7);
        Audit::record($request->user(), 'annex_d.generated', $record, ['activity_version_id' => $data['activity_version_id'],
            'template' => $data['template'], 'signed' => false]);

        return response($pdf->output(), 200, $this->headers() + ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Diagnostic-'.$data['activity_version_id'].'.pdf"']);
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
    }

    /** @return array<string, string> */
    private function previewHeaders(): array
    {
        return ['Content-Security-Policy' => "default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'"];
    }
}
