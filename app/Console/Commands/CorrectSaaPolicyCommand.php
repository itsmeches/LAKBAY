<?php

namespace App\Console\Commands;

use App\Actions\Submissions\CorrectSaaPolicy;
use App\Models\PolicyVersion;
use App\Services\Compliance\PolicyRules;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lakbay:correct-saa {--policy= : Source policy code; defaults to CMO24-2026} {--apply : Apply the additive correction; otherwise dry-run}')]
#[Description('Copy the CMO candidate without ordinary Category 3 SAA; preserve all existing records')]
class CorrectSaaPolicyCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CorrectSaaPolicy $action): int
    {
        $source = PolicyVersion::where('code', $this->option('policy') ?: PolicyRules::CMO_CODE)->firstOrFail();
        $this->line(json_encode($action->plan($source), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        if ($this->option('apply')) {
            $policy = $action->execute($source);
            $this->info('Policy available: '.$policy->code.' ('.$policy->status.'). No existing rows updated or deleted.');
        } else {
            $this->info('Dry-run only. Use --apply to create the candidate copy.');
        }

        return self::SUCCESS;
    }
}
