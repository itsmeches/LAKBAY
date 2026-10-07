<?php

namespace App\Console\Commands;

use App\Jobs\NotifyInstitution;
use App\Models\Activity;
use App\Models\CompletionReport;
use App\Models\NotificationDelivery;
use App\Models\PolicyVersion;
use App\Models\RegionalReport;
use App\Models\User;
use App\Notifications\WorkflowNotice;
use App\Services\Compliance\PolicyRules;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'lakbay:reminders';

    protected $description = 'Queue deduplicated reminders for known outstanding completion reports.';

    public function handle(): int
    {
        Activity::whereNotNull('actual_ended_at')->chunkById(100, function ($activities) {
            foreach ($activities as $a) {
                if (! CompletionReport::where('activity_id', $a->id)->exists()) {
                    NotifyInstitution::dispatch($a->institution_id, 'roc-'.$a->id.'-'.now()->format('Y-m-d'), 'Completion report reminder', 'An activity has a recorded conclusion and an outstanding completion report. Sign in to review it.');
                }
            }
        });
        $today = now()->timezone('Asia/Manila');
        $activeCmo = PolicyVersion::where('status', 'confirmed')->where('effective_on', '<=', $today->toDateString())->get()->contains(fn ($policy) => PolicyRules::isCmo($policy) && PolicyRules::hasVerifiedActivation($policy));
        if ($activeCmo && $today->month === 8 && in_array($today->day, [1, 15, 25], true)) {
            User::where('is_active', true)->whereNotNull('email_verified_at')->whereNotNull('region_id')->get()->filter(fn ($user) => $user->can('reports.export') && $user->can('panel.ched'))->each(function ($user) use ($today) {
                $sent = RegionalReport::where('region_id', $user->region_id)->where('report_type', 'annual_monitoring')->where('reporting_year', $today->year)->whereNotNull('sent_to_osds_at')->exists();
                if (! $sent) {
                    $delivery = NotificationDelivery::firstOrCreate(['user_id' => $user->id, 'event_key' => 'annual-me-'.$today->toDateString(), 'channel' => 'mail']);
                    if (! $delivery->delivered_at) {
                        $user->notify(new WorkflowNotice('Annual regional M&E report reminder', 'Prepare and record OSDS dispatch for the annual report due in the last week of August. The exact cutoff requires CHED confirmation.', 'ched'));
                        $delivery->update(['delivered_at' => now()]);
                    }
                }
            });
        }
        $this->info('Reminders queued.');

        return 0;
    }
}
