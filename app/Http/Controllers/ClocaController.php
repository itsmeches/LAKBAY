<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Submission;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Reporting\ClocaBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClocaController extends Controller
{
    public function calendar(Request $request, Calendar $calendar, ClocaBuilder $builder): Response
    {
        app(Access::class)->authorize($request->user(), $calendar->institution_id, 'records.view');
        abort_if($calendar->activities()->count() > 500, 422, 'Split calendars larger than 500 activities before printing.');
        $data = $builder->snapshot($calendar);

        return $this->render($request, $builder, $data, 'CLOCA-calendar-'.$calendar->id,
            route('cloca.calendar', ['calendar' => $calendar->id, 'download' => 1]), null, $calendar);
    }

    public function submission(Request $request, string $publicId, ClocaBuilder $builder): Response
    {
        $submission = Submission::where('public_id', $publicId)->firstOrFail();
        app(Access::class)->authorize($request->user(), $submission->institution_id, 'records.view');
        abort_unless($submission->calendar_id && ! empty($submission->snapshot['annex_b']), 409,
            'This historical submission has no frozen Annex B data. Retrieve its original signed CLOCA from the Document library.');
        $data = $submission->snapshot['annex_b'];

        return $this->render($request, $builder, $data, 'CLOCA-'.$submission->reference,
            route('cloca.submission', ['publicId' => $submission->public_id, 'download' => 1]), $submission->reference, $submission);
    }

    /** @param array<string, mixed> $data */
    private function render(Request $request, ClocaBuilder $builder, array $data, string $filename, string $downloadUrl, ?string $reference, Calendar|Submission $record): Response
    {
        abort_unless(($data['renderer_version'] ?? null) === 1, 409, 'This Annex B rendering version is not supported.');
        abort_if(count($data['rows']) > 500, 422, 'This calendar is too large for a synchronous PDF.');
        $missing = $builder->missingFields($data);
        $download = $request->boolean('download');
        abort_if($download && $missing !== [], 422, 'Complete CLOCA print fields before downloading: '.implode('; ', $missing));
        $values = ['cloca' => $data, 'missing' => $missing, 'letterhead' => $builder->letterhead($request->user(), $data),
            'pdf' => $download, 'downloadUrl' => $downloadUrl, 'reference' => $reference];
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if (! $download) {
            return response()->view('documents.cloca', $values, 200, $headers + [
                'Content-Security-Policy' => "default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'",
            ]);
        }
        $pdf = Pdf::loadView('documents.cloca', $values)->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(475, 823, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica'), 7);
        $contents = $pdf->output();
        Audit::record($request->user(), 'annex_b.generated', $record,
            ['reference' => $reference, 'template' => $data['template'], 'signed' => false]);

        return response($contents, 200, $headers + ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"']);
    }
}
