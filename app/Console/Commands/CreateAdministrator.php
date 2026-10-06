<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Models\User;
use Illuminate\Console\Command;

class CreateAdministrator extends Command
{
    protected $signature = 'lakbay:admin {email} {--name=Administrator}';

    protected $description = 'Create the initial verified system administrator (no confidential case permission).';

    public function handle(): int
    {
        if (User::where('email', $this->argument('email'))->exists()) {
            $this->error('Account already exists.');

            return 1;
        }
        $password = $this->secret('Password (minimum 12 characters)');
        if (strlen($password ?? '') < 12) {
            $this->error('Password must be at least 12 characters.');

            return 1;
        }
        $user = User::create(['name' => $this->option('name'), 'email' => $this->argument('email'), 'password' => $password]);
        $user->forceFill(['region_id' => Region::where('code', 'IV-A')->firstOrFail()->id, 'email_verified_at' => now()])->save();
        $user->assignRole('system_administrator');
        $this->info('Administrator created. Sign in at /ched and set up MFA.');

        return 0;
    }
}
