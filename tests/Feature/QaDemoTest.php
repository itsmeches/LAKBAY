<?php

namespace Tests\Feature;

use App\Jobs\ScanDocument;
use App\Models\Activity;
use App\Models\CaseRecord;
use App\Models\CompletionReport;
use App\Models\Document;
use App\Models\IncidentReport;
use App\Models\Institution;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\QaDemoScenarioSeeder;
use Database\Seeders\QaDemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QaDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Storage::fake('private');
        Storage::fake('local');
    }

    public function test_demo_is_idempotent_and_preserves_passwords_and_quarantine(): void
    {
        $this->seed(QaDemoSeeder::class);
        $user = User::where('email', 'suc.official@example.test')->firstOrFail();
        $this->assertTrue(Hash::check(QaDemoSeeder::PASSWORD, $user->password));
        $user->update(['password' => 'Changed-local-only!2026']);
        $this->seed(QaDemoSeeder::class);
        $this->assertTrue(Hash::check('Changed-local-only!2026', $user->fresh()->password));
        $this->assertSame(20, User::count());
        $this->assertSame(4, Institution::count());
        $this->assertSame(9, Activity::count());
        $this->assertSame(23, Document::count());
        $this->assertSame(0, Document::where('scan_status', 'clean')->count());
        $this->assertNull($user->app_authentication_secret);
        Queue::assertPushed(ScanDocument::class, 23);
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('hei')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('ched')));
    }

    public function test_scenarios_refuse_unscanned_documents(): void
    {
        $this->seed(QaDemoSeeder::class);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Scenarios never bypass malware scanning');
        $this->seed(QaDemoScenarioSeeder::class);
    }

    public function test_scenarios_are_idempotent_after_scanning_through_the_job(): void
    {
        $this->seed(QaDemoSeeder::class);
        config(['lakbay.scanner_command' => 'test-scanner']);
        Process::fake(['*' => Process::result(exitCode: 0)]);
        foreach (Document::all() as $document) {
            (new ScanDocument($document->id))->handle();
        }
        $this->seed(QaDemoScenarioSeeder::class);
        $this->assertSame(1, Submission::where('status', 'corrections_requested')->count());
        $this->assertSame(1, Submission::where('status', 'acknowledged')->count());
        $this->seed(QaDemoScenarioSeeder::class);
        $this->assertSame(4, Submission::count());
        $this->assertSame(1, Submission::where('status', 'corrections_requested')->count());
        $this->assertSame(1, Submission::where('status', 'acknowledged')->count());
        $this->assertSame(1, CompletionReport::count());
        $this->assertSame(1, IncidentReport::count());
        $this->assertSame(1, CaseRecord::count());
        $this->assertTrue(IncidentReport::first()->is_late);
    }

    public function test_demo_refuses_production(): void
    {
        $this->app->instance('env', 'production');
        $this->artisan('lakbay:demo')->assertFailed();
        $this->expectException(\RuntimeException::class);
        QaDemoSeeder::assertIsolated();
    }

    public function test_demo_refuses_an_existing_local_database_and_real_mail(): void
    {
        $this->app->instance('env', 'local');
        config(['database.connections.sqlite.database' => base_path('database/database.sqlite'), 'mail.default' => 'smtp']);
        $this->expectException(\RuntimeException::class);
        QaDemoSeeder::assertIsolated();
    }
}
