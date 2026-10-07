<?php

namespace App\Http\Controllers;

use App\Actions\Activities\PrepareConsentForm;
use App\Models\ActivityVersion;
use App\Models\Document;
use App\Services\Access;
use App\Services\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ConsentFormController extends Controller
{
    public function form(Request $request, ActivityVersion $version, PrepareConsentForm $action): Response
    {
        $data = $action->execute($request->user(), $version);

        return $this->formResponse($request, $version, $data);
    }

    public function generate(Request $request, ActivityVersion $version, PrepareConsentForm $action): Response
    {
        $action->authorize($request->user(), $version);
        try {
            $data = $action->execute($request->user(), $version, $request->all());
        } catch (ValidationException $exception) {
            return $this->formResponse($request, $version, $action->execute($request->user(), $version),
                collect($request->all())->only(array_keys($action->rules()))->all(), $exception->errors(), 422);
        }
        $download = $request->input('output') === 'pdf';
        $values = ['consent' => $data, 'pdf' => $download,
            'formUrl' => route('consent.form', $version)];
        if (! $download) {
            return response()->view('documents.consent', $values, 200, $this->headers());
        }
        $pdf = Pdf::loadView('documents.consent', $values)->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(475, 823, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica'), 7);
        Audit::record($request->user(), 'annex_l.generated', $version->activity, [
            'activity_version_id' => $version->id, 'template' => $data['template'], 'signed' => false,
            'personal_fields_persisted' => false,
        ]);

        return response($pdf->output(), 200, $this->headers() + ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Consent-V'.$version->id.'.pdf"']);
    }

    /** @param array<string, mixed> $data
     * @param  array<string, mixed>  $fields
     * @param  array<string, list<string>>  $errors
     */
    private function formResponse(Request $request, ActivityVersion $version, array $data, array $fields = [], array $errors = [], int $status = 200): Response
    {
        $images = Document::where('institution_id', $version->activity->institution_id)->where('scan_status', 'clean')
            ->where('confidentiality', 'institutional')->whereIn('mime', ['image/png', 'image/jpeg'])
            ->where('size', '<=', 2 * 1024 * 1024)->get()->filter(fn ($document) => app(Access::class)->document($request->user(), $document))
            ->pluck('original_name', 'id');

        return response()->view('documents.consent-form', ['consent' => $data, 'fields' => $fields,
            'validationErrors' => $errors, 'letterheads' => $images,
            'actionUrl' => route('consent.generate', $version)], $status, $this->headers());
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Content-Security-Policy' => "default-src 'none'; img-src data:; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'self'"];
    }
}
