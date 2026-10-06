<?php

namespace App\Actions\Operations;

use App\Models\CorrectiveAction;
use App\Models\MonitoringRecord;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManageCorrectiveAction
{
    public function create(User $actor, MonitoringRecord $monitoring, array $data): CorrectiveAction
    {
        app(Access::class)->authorize($actor, $monitoring->institution_id, 'monitoring.manage', false, true);
        $data = Validator::make($data, ['action' => 'required|string|max:10000', 'responsible_party' => 'required|string|max:255', 'due_on' => 'required|date'])->validate();

        return DB::transaction(function () use ($actor, $monitoring, $data) {
            $r = CorrectiveAction::create($data + ['institution_id' => $monitoring->institution_id, 'monitoring_record_id' => $monitoring->id, 'created_by' => $actor->id]);
            Audit::record($actor, 'corrective_action.created', $r);

            return $r;
        });
    }

    public function respond(User $actor, CorrectiveAction $record, string $response): void
    {
        app(Access::class)->authorize($actor, $record->institution_id, 'reports.submit', true);
        Validator::make(['response' => $response], ['response' => 'required|string|max:10000'])->validate();
        DB::transaction(function () use ($actor, $record, $response) {
            $r = CorrectiveAction::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_if($r->status === 'closed', 409);
            $r->update(['response' => $response, 'status' => 'responded']);
            Audit::record($actor, 'corrective_action.responded', $r, ['response' => $response]);
        });
    }

    public function close(User $actor, CorrectiveAction $record): void
    {
        app(Access::class)->authorize($actor, $record->institution_id, 'monitoring.manage', false, true);
        DB::transaction(function () use ($actor, $record) {
            $r = CorrectiveAction::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_unless($r->status === 'responded', 409, 'An HEI response is required before closure.');
            $r->update(['status' => 'closed']);
            Audit::record($actor, 'corrective_action.closed', $r);
        });
    }
}
