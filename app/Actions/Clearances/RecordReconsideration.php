<?php

namespace App\Actions\Clearances;

use App\Models\ClearanceRequest;
use App\Models\Document;
use App\Models\Reconsideration;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordReconsideration
{
    public function execute(User $actor, ClearanceRequest $request, string $filedAt, string $grounds, int $documentId): Reconsideration
    {
        app(Access::class)->authorize($actor, $request->institution_id, 'cases.sensitive', false, true);

        return DB::transaction(function () use ($actor, $request, $filedAt, $grounds, $documentId) {
            $r = ClearanceRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($r->decision !== 'denied' || ! $r->decision_received_at || $r->reconsideration()->exists()) {
                throw ValidationException::withMessages(['clearance' => 'Only one reconsideration of a recorded denial is allowed.']);
            }
            $d = Document::findOrFail($documentId);
            abort_unless($d->institution_id === $r->institution_id && $d->scan_status === 'clean' && $d->is_signed && $d->documentType->code === 'reconsideration', 422);
            $due = $r->decision_received_at->addDays(15);
            $filed = CarbonImmutable::parse($filedAt, config('lakbay.timezone'))->utc();
            $v = Reconsideration::create(['clearance_request_id' => $r->id, 'filed_at' => $filed, 'due_at' => $due, 'is_late' => $filed->gt($due), 'grounds' => $grounds, 'document_id' => $documentId, 'created_by' => $actor->id]);
            Audit::record($actor, 'clearance.reconsideration_recorded', $r, ['is_late' => $v->is_late]);

            return $v;
        });
    }
}
