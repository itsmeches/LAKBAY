<?php

namespace App\Actions\Clearances;

use App\Models\ClearanceRequest;
use App\Models\Document;
use App\Models\Reconsideration;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;

class RecordReconsiderationDecision
{
    public function execute(User $actor, ClearanceRequest $request, string $decision, int $documentId): void
    {
        app(Access::class)->authorize($actor, $request->institution_id, 'cases.sensitive', false, true);
        abort_unless(in_array($decision, ['granted', 'denied'], true), 422);
        DB::transaction(function () use ($actor, $request, $decision, $documentId) {
            $r = Reconsideration::where('clearance_request_id', $request->id)->lockForUpdate()->firstOrFail();
            abort_if($r->decision !== null, 409, 'Reconsideration decision already recorded.');
            $d = Document::findOrFail($documentId);
            abort_unless($d->institution_id === $request->institution_id && $d->scan_status === 'clean' && $d->is_signed && $d->documentType->code === 'ceb_decision', 422);
            $r->update(['decision' => $decision, 'decision_document_id' => $d->id]);
            Audit::record($actor, 'reconsideration.external_decision_recorded', $request, ['reconsideration_id' => $r->id, 'decision' => $decision]);
        });
    }
}
