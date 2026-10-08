<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class ScanDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $documentId) {}

    public function handle(): void
    {
        $d = Document::findOrFail($this->documentId);
        if ($d->scan_status === 'clean' || $d->scan_status === 'infected') {
            return;
        }
        $scanner = config('lakbay.scanner_command');
        if (! $scanner) {
            throw new \RuntimeException('No malware scanner configured. Document remains quarantined.');
        }
        $arguments = [$scanner, '--no-summary'];
        if ($database = config('lakbay.scanner_database_path')) {
            $arguments[] = '--database='.str_replace('/', DIRECTORY_SEPARATOR, $database);
        }
        $filePath = realpath(Storage::disk($d->disk)->path($d->path));
        if ($filePath === false) {
            throw new \RuntimeException('The quarantined document is missing.');
        }
        $arguments[] = $filePath;
        $result = Process::timeout(120)->run($arguments);
        if ($result->exitCode() === 0) {
            if (hash_file('sha256', Storage::disk($d->disk)->path($d->path)) !== $d->sha256) {
                throw new \RuntimeException('Document hash mismatch.');
            }$d->update(['scan_status' => 'clean']);
        } elseif ($result->exitCode() === 1 && preg_match('/:\s+[^\r\n]+\s+FOUND\s*$/m', $result->output()) === 1) {
            $d->update(['scan_status' => 'infected']);
        } else {
            throw new \RuntimeException('Malware scanner failed. Document remains quarantined.');
        }
    }
}
