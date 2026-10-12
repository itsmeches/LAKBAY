<?php

namespace App\Jobs;

use App\Models\Institution;
use App\Models\NotificationDelivery;
use App\Notifications\WorkflowNotice;
use App\Services\Access;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyInstitution implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $institutionId, public string $key, public string $subject, public string $message) {}

    public function handle(): void
    {
        foreach (Institution::findOrFail($this->institutionId)->users()->whereNotNull('institution_user.verified_at')->where('users.is_active', true)->whereNotNull('users.email_verified_at')->get() as $u) {
            if (! $u->can('panel.hei') || ! $u->can('records.view') || ! app(Access::class)->owns($u, $this->institutionId)) {
                continue;
            }
            $d = NotificationDelivery::firstOrCreate(['user_id' => $u->id, 'event_key' => $this->key, 'channel' => 'mail']);
            if ($d->delivered_at) {
                continue;
            }
            $u->notify(new WorkflowNotice($this->subject, $this->message));
            $d->update(['delivered_at' => now()]);
        }
    }
}
