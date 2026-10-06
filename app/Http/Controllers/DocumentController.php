<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController
{
    public function preview(Request $request, string $publicId): StreamedResponse
    {
        $document = Document::where('public_id', $publicId)->firstOrFail();
        abort_unless(app(Access::class)->document($request->user(), $document), 403);
        abort_unless($document->scan_status === 'clean', 409, 'Document is awaiting a successful malware scan.');
        abort_unless(in_array($document->mime, ['image/png', 'image/jpeg'], true), 415, 'Preview supports PNG and JPG images.');
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);
        Audit::record($request->user(), 'document.previewed', $document);

        return Storage::disk($document->disk)->response($document->path, basename($document->original_name), [
            'Content-Type' => $document->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ], 'inline');
    }

    public function __invoke(Request $request, string $publicId)
    {
        $d = Document::where('public_id', $publicId)->firstOrFail();
        abort_unless(app(Access::class)->document($request->user(), $d), 403);
        abort_unless($d->scan_status === 'clean', 409, 'Document is quarantined.');
        Audit::record($request->user(), 'document.downloaded', $d);

        return Storage::disk($d->disk)->download($d->path, basename($d->original_name), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
