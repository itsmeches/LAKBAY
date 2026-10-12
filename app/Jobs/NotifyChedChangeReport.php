<?php

namespace App\Jobs;

use App\Models\ChangeReport;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\WorkflowNotice;
use App\Services\Access;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class NotifyChedChangeReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $reportId) {}

    /** @return array<int, WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('ched-change-'.$this->reportId))->releaseAfter(10)->expireAfter(120)];
    }

    public function handle(): void
    {
        $report = ChangeReport::findOrFail($this->reportId);
        $access = app(Access::class);
        foreach (User::permission('submissions.review')->where('is_active', true)->whereNotNull('email_verified_at')->get() as $recipient) {
            if (! $recipient->can('records.view') || ! $access->regional($recipient, $report->institution_id)) {
                continue;
            }
            $delivery = NotificationDelivery::firstOrCreate(['user_id' => $recipient->id, 'event_key' => 'change-intake-'.$report->id, 'channel' => 'mail']);
            DB::transaction(function () use ($delivery, $recipient, $report, $access): void {
                $locked = NotificationDelivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
                $recipient = $recipient->fresh();
                if ($locked->delivered_at || ! $recipient || ! $recipient->can('submissions.review') || ! $recipient->can('records.view') || ! $access->regional($recipient, $report->institution_id)) {
                    return;
                }
                $recipient->notify(new WorkflowNotice('Change report received', 'Change intake #'.$report->id.' is available. Sign in to review the filed notice, signed report or follow-up. Receipt does not authorize conduct.', 'ched'));
                $locked->update(['delivered_at' => now()]);
            });
        }
    }
}
