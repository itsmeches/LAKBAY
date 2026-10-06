<?php

namespace App\Http\Controllers;

use App\Models\FormTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicTemplateController extends Controller
{
    public static function available(): Builder
    {
        return FormTemplate::query()->where('is_public', true)->whereHas('policyVersion', fn ($q) => $q->where('status', 'confirmed')->where('effective_on', '<=', today(config('lakbay.timezone'))))->where(fn ($q) => $q->whereNull('active_from')->orWhere('active_from', '<=', today(config('lakbay.timezone'))))->where(fn ($q) => $q->whereNull('active_until')->orWhere('active_until', '>=', today(config('lakbay.timezone'))))->whereHas('document', fn ($q) => $q->where('confidentiality', 'institutional')->where('is_signed', false)->where('scan_status', 'clean'));
    }

    public function index(): View
    {
        return view('guidelines', ['templates' => self::available()->orderBy('title')->get()]);
    }

    public function download(int $id): StreamedResponse
    {
        $t = self::available()->findOrFail($id);

        return Storage::disk('private')->download($t->document->path, $t->code.'-v'.$t->version.'.pdf', ['Content-Type' => $t->document->mime, 'X-Content-Type-Options' => 'nosniff']);
    }
}
