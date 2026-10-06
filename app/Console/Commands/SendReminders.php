<?php

namespace App\Console\Commands;

use App\Jobs\NotifyInstitution;
use App\Models\Activity;
use App\Models\CompletionReport;
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
        $this->info('Reminders queued.');

        return 0;
    }
}
