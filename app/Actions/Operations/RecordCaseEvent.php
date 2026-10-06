<?php

namespace App\Actions\Operations;

use App\Models\CaseEvent;
use App\Models\CaseRecord;
use App\Models\Document;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;

class RecordCaseEvent
{
    public function execute(User $actor, CaseRecord $case, string $status, string $notes, ?int $documentId = null): void
    {
        app(Access::class)->authorize($actor, $case->institution_id, 'cases.sensitive', false, true);
        abort_unless(in_array($status, ['received', 'referred', 'investigating', 'decided', 'closed'], true) && trim($notes), 422);
        $d = null;
        if ($documentId) {
            $d = Document::findOrFail($documentId);
            abort_unless($d->institution_id === $case->institution_id && $d->scan_status === 'clean', 422);
        }
        if (in_array($status, ['decided', 'closed'], true)) {
            abort_unless($d !== null && $d->is_signed && $d->documentType->code === 'official_order', 422, 'A signed official decision or closure document is required.');
        }
        DB::transaction(function () use ($actor, $case, $status, $notes, $documentId) {
            $c = CaseRecord::whereKey($case->id)->lockForUpdate()->firstOrFail();
            CaseEvent::create(['case_record_id' => $c->id, 'type' => $status, 'occurred_at' => now(), 'notes' => $notes, 'document_id' => $documentId, 'created_by' => $actor->id]);
            $c->update(['status' => $status, 'closed_at' => $status === 'closed' ? now() : null]);
            Audit::record($actor, 'case.status_recorded', $c, ['status' => $status]);
        });
    }
}
