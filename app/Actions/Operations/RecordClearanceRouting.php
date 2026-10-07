<?php

namespace App\Actions\Operations;

use App\Models\ClearanceRequest;
use App\Models\Document;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecordClearanceRouting
{
    public function execute(User $actor, ClearanceRequest $request, array $data): void
    {
        app(Access::class)->authorize($actor, $request->institution_id, 'cases.sensitive', false, true);
        Validator::make($data, ['stage' => 'required|in:chedro_endorsement,osds_received,osds_review,ceb_routed', 'reference' => 'required|string|max:255',
            'occurred_at' => 'required|date', 'document_id' => 'required|integer'])->validate();
        $occurred = CarbonImmutable::parse($data['occurred_at'], config('lakbay.timezone'))->utc();
        abort_if($occurred->isFuture(), 422, 'Routing time cannot be in the future (Asia/Manila).');
        $data['occurred_at'] = $occurred->toIso8601String();
        $doc = Document::findOrFail($data['document_id']);
        abort_unless($doc->institution_id === $request->institution_id && $doc->scan_status === 'clean' && $doc->documentType->code === 'clearance_correspondence' && app(Access::class)->document($actor, $doc), 422);
        DB::transaction(function () use ($actor, $request, $data) {
            $r = ClearanceRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            abort_if($r->decision !== null, 409, 'Routing cannot be appended after the external decision.');
            $history = $r->routing_history ?? [];
            $history[] = $data + ['recorded_by' => $actor->id, 'recorded_at' => now()->toIso8601String()];
            $fields = ['routing_history' => $history];
            if ($data['stage'] === 'chedro_endorsement') {
                $fields['endorsement_reference'] = $data['reference'];
            }
            if ($data['stage'] === 'osds_received') {
                $fields['osds_reference'] = $data['reference'];
            }
            $r->update($fields);
            Audit::record($actor, 'clearance.routing_recorded', $r, ['stage' => $data['stage']]);
        });
    }
}
