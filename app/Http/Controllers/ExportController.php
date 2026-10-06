<?php

namespace App\Http\Controllers;

use App\Models\Export;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController
{
    public function __invoke(Request $request, Export $export)
    {
        abort_unless($export->requested_by === $request->user()->id && $request->user()->can('reports.export') && $request->user()->is_active, 403);
        abort_unless($export->status === 'ready' && $export->expires_at?->isFuture(), 410);
        Audit::record($request->user(), 'export.downloaded', $export);

        return Storage::disk('private')->download($export->path, 'lakbay-regional-report.'.pathinfo($export->path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store']);
    }
}
