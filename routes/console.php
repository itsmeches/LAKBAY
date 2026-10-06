<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('lakbay:reminders')->dailyAt('08:00')->timezone('Asia/Manila')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->daily();
