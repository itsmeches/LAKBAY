<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class BootstrapQaDemo extends Command
{
    protected $signature = 'lakbay:demo {--reset : Reset ONLY the isolated QA database} {--prepare : Prepare scenarios after genuine malware scans} {--scanner= : ClamAV executable for a new QA environment} {--scanner-database= : ClamAV signature directory for a new QA environment}';

    protected $description = 'Create isolated fictional QA accounts without changing the normal database.';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing']) || app()->configurationIsCached()) {
            $this->error('Run locally with uncached configuration. Production and cached configuration are refused.');

            return self::FAILURE;
        }
        $root = str_replace('\\', '/', base_path('storage/app/qa-demo'));
        $database = str_replace('\\', '/', base_path('database/qa-demo.sqlite'));
        $environment = base_path('.env.local');
        foreach (['', '/app/private', '/app/fixtures', '/framework/cache/data', '/framework/sessions', '/framework/views', '/logs'] as $directory) {
            File::ensureDirectoryExists($root.$directory);
        }
        if (! File::exists($environment)) {
            $scanner = str_replace('\\', '/', (string) $this->option('scanner'));
            $signatures = str_replace('\\', '/', (string) $this->option('scanner-database'));
            foreach ([$scanner, $signatures, $root, $database] as $value) {
                if (preg_match('/[\r\n"$]/', $value)) {
                    $this->error('QA paths may not contain quotes, newlines, or dollar signs.');

                    return self::FAILURE;
                }
            }
            File::put($environment, implode("\n", [
                'APP_NAME="LAKBAY QA"', 'APP_ENV=local', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), 'APP_DEBUG=true',
                'APP_URL=http://127.0.0.1:8001', 'DB_CONNECTION=sqlite', 'DB_DATABASE="'.$database.'"',
                'LARAVEL_STORAGE_PATH="'.$root.'"', 'SESSION_DRIVER=database', 'SESSION_COOKIE=lakbay_qa_session',
                'SESSION_LIFETIME=120', 'CACHE_STORE=database', 'QUEUE_CONNECTION=database', 'FILESYSTEM_DISK=local',
                'MAIL_MAILER=log', 'MAIL_FROM_ADDRESS=qa@example.test', 'MAIL_FROM_NAME="LAKBAY QA"',
                'LOG_CHANNEL=single', 'BCRYPT_ROUNDS=4', 'LAKBAY_ALLOW_DRAFT_POLICY=true',
                'LAKBAY_HOLIDAY_VERSION=qa-fictional', 'LAKBAY_SCANNER_COMMAND="'.$scanner.'"',
                'LAKBAY_SCANNER_DATABASE_PATH="'.$signatures.'"', '',
            ]));
        }
        $contents = File::get($environment);
        foreach (['APP_ENV=local', 'DB_CONNECTION=sqlite', 'DB_DATABASE="'.$database.'"', 'LARAVEL_STORAGE_PATH="'.$root.'"', 'MAIL_MAILER=log'] as $required) {
            if (! in_array($required, preg_split('/\r?\n/', $contents), true)) {
                $this->error('Existing .env.local does not match the isolated QA configuration. It was not overwritten.');

                return self::FAILURE;
            }
        }
        if (! File::exists($database)) {
            File::put($database, '');
        }
        $arguments = $this->option('prepare')
            ? ['db:seed', '--class=QaDemoScenarioSeeder', '--force']
            : ($this->option('reset') ? ['migrate:fresh', '--seed', '--seeder=QaDemoSeeder', '--force'] : ['migrate', '--seed', '--seeder=QaDemoSeeder', '--force']);
        $result = Process::path(base_path())->timeout(300)->env([
            'APP_ENV' => 'local', 'APP_CONFIG_CACHE' => $root.'/config.php', 'APP_URL' => 'http://127.0.0.1:8001',
            'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '',
            'LARAVEL_STORAGE_PATH' => $root, 'MAIL_MAILER' => 'log', 'QUEUE_CONNECTION' => 'database',
            'CACHE_STORE' => 'database', 'SESSION_DRIVER' => 'database',
        ])->run([PHP_BINARY, 'artisan', ...$arguments, '--env=local', '--no-interaction'], function (string $type, string $output): void {
            $this->output->write($output);
        });
        if (! $result->successful()) {
            return self::FAILURE;
        }
        $this->info('QA database: database/qa-demo.sqlite; normal .env and database are untouched.');
        $this->line('QA passwords: Lakbay-QA-Only!2026. Enroll MFA at first sign-in; there is no bypass.');
        $this->line('Scan: php artisan queue:work --env=local --stop-when-empty --tries=1 --timeout=180');
        $this->line('Scenarios: php artisan lakbay:demo --prepare');
        $this->line('Serve: php artisan serve --env=local --host=127.0.0.1 --port=8001');

        return self::SUCCESS;
    }
}
