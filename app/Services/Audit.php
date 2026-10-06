<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Audit
{
    public static function record(User $actor, string $action, Model $record, array $metadata = []): void
    {
        AuditEvent::create(['actor_id' => $actor->id, 'institution_id' => $record->institution_id, 'action' => $action, 'record_type' => $record->getMorphClass(), 'record_id' => $record->id, 'correlation_id' => (string) Str::uuid(), 'metadata' => $metadata]);
    }
}
