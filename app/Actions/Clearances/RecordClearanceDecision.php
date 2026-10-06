<?php

namespace App\Actions\Clearances;

use App\Models\ClearanceRequest;
use App\Models\Document;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordClearanceDecision
{
    public function execute(User $actor, ClearanceRequest $request, string $decision, int $documentId, string $decidedAt, string $receivedAt)
    {
        app(Access::class)->authorize($actor, $request->institution_id, 'cases.sensitive', false, true);
        if (! in_array($decision, ['granted', 'denied'], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose granted or denied.']);
        }

        return DB::transaction(function () use ($actor, $request, $decision, $documentId, $decidedAt, $receivedAt) {
            $r = ClearanceRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($r->decision) {
                throw ValidationException::withMessages(['decision' => 'Decision already recorded.']);
            }
            $d = Document::findOrFail($documentId);
            abort_unless($d->institution_id === $r->institution_id && $d->scan_status === 'clean' && $d->is_signed && $d->documentType->code === 'ceb_decision', 422);
            $r->update(['decision' => $decision, 'status' => $decision, 'decision_document_id' => $documentId, 'decided_at' => CarbonImmutable::parse($decidedAt, config('lakbay.timezone'))->utc(), 'decision_received_at' => CarbonImmutable::parse($receivedAt, config('lakbay.timezone'))->utc()]);
            Audit::record($actor, 'clearance.external_decision_recorded', $r, ['decision' => $decision]);

            return $r;
        });
    }
}
