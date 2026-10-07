<?php

namespace Database\Seeders;

use App\Actions\Activities\SaveActivity;
use App\Actions\Documents\UploadDocument;
use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\Campus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class QaDemoSeeder extends Seeder
{
    public const PASSWORD = 'Lakbay-QA-Only!2026';

    public static function assertIsolated(): void
    {
        $testing = app()->environment('testing') && config('database.connections.sqlite.database') === ':memory:';
        $local = app()->environment('local')
            && str_replace('\\', '/', (string) config('database.connections.sqlite.database')) === str_replace('\\', '/', base_path('database/qa-demo.sqlite'))
            && str_replace('\\', '/', storage_path()) === str_replace('\\', '/', base_path('storage/app/qa-demo'));
        if (config('database.default') !== 'sqlite' || (! $testing && ! $local) || ! in_array(config('mail.default'), ['log', 'array'], true)) {
            throw new \RuntimeException('QA seeding requires the isolated local QA database or in-memory tests with non-delivering mail.');
        }
    }

    public function run(): void
    {
        self::assertIsolated();
        $this->call(DatabaseSeeder::class);
        $region = Region::where('code', 'IV-A')->firstOrFail();
        $other = Region::firstOrCreate(['code' => 'III'], ['name' => 'QA other region']);
        foreach (['suc', 'luc', 'private', 'outside'] as $kind) {
            $institution = Institution::firstOrCreate(['ched_code' => 'QA-'.strtoupper($kind)], [
                'region_id' => $kind === 'outside' ? $other->id : $region->id, 'name' => 'QA '.ucfirst($kind).' HEI (fictional)',
                'institution_type' => $kind === 'outside' ? 'private' : $kind, 'address' => 'Fictional QA address', 'email' => $kind.'@example.test',
            ]);
            Campus::firstOrCreate(['institution_id' => $institution->id, 'name' => 'QA Main Campus'], ['locality' => 'QA Calamba']);
            foreach (['official' => 'hei_submitter', 'staff' => 'hei_preparer', 'faculty' => 'hei_preparer'] as $person => $role) {
                $user = $this->account($kind.'.'.$person.'@example.test', $role);
                if (! $user->institutions()->whereKey($institution->id)->exists()) {
                    $user->institutions()->attach($institution->id, ['position' => 'QA '.$person, 'is_authorized' => $person === 'official', 'verified_at' => now()]);
                }
            }
            $period = AcademicPeriod::firstOrCreate(['institution_id' => $institution->id, 'label' => 'QA AY 2026-2027'], [
                'starts_on' => '2026-09-01', 'ends_on' => '2027-08-31', 'term' => 'First semester',
            ]);
            $official = User::where('email', $kind.'.official@example.test')->firstOrFail();
            $calendar = Calendar::firstOrCreate(['institution_id' => $institution->id, 'title' => 'QA annual CLOCA'], [
                'academic_period_id' => $period->id, 'responsible_office' => 'QA OSAS', 'created_by' => $official->id,
            ]);
            $plans = $kind === 'suc' ? ['annual' => 1, 'category2' => 2, 'category3' => 3, 'escalated3' => 3, 'completed' => 2, 'incident' => 2] : ['isolation' => 2];
            foreach ($plans as $name => $category) {
                if (Activity::where('institution_id', $institution->id)->where('title', 'QA '.$name)->exists()) {
                    continue;
                }
                $start = in_array($name, ['completed', 'incident'], true) ? CarbonImmutable::now('Asia/Manila')->subDay()->startOfDay()->setHour(9) : CarbonImmutable::now('Asia/Manila')->addDays(21)->startOfDay()->setHour(9);
                app(SaveActivity::class)->execute($official, [
                    'institution_id' => $institution->id, 'academic_period_id' => $period->id, 'calendar_id' => $category === 1 ? $calendar->id : null,
                    'title' => 'QA '.$name, 'category' => $category, 'academic_nature' => 'non_curricular', 'activity_type' => 'Learning visit',
                    'initiator' => $category === 3 ? 'student' : 'hei', 'program' => 'BS QA Studies',
                    'description' => 'Fictional supervised learning visit.', 'learning_objectives' => 'Practice safe planning and compliance.',
                    'responsible_office' => 'QA OSAS', 'starts_at' => $start, 'ends_at' => $start->addHours(8), 'venue' => 'QA Learning Venue',
                    'student_count' => 20, 'other_count' => 1,
                    'conditions' => ['requires_physical_activity' => false, 'requires_travel' => false, 'strenuous' => $name === 'escalated3'],
                    'authorization' => ['official' => 'QA HEI Head', 'date' => now('Asia/Manila')->toDateString()],
                    'personnel' => [['role' => 'pic', 'name' => 'QA Faculty One', 'position' => 'Faculty', 'contact' => 'faculty@example.test',
                        'qualifications' => 'QA training reference', 'hei_employed' => true, 'accompanies_activity' => true,
                        'first_aid_trained' => true, 'first_aid_reference' => 'QA-FIRST-AID-001']],
                    'preparation' => ['inclusivity' => 'QA-INCLUSIVE', 'parallel_learning' => 'QA-PADP', 'grievance_mechanism' => 'QA-GRIEVANCE', 'student_participation' => 'QA-CONSULT', 'emergency_preparedness' => 'QA-RACP'],
                ]);
            }
        }
        foreach (['reviewer' => 'ched_reviewer', 'monitor' => 'ched_monitor', 'director' => 'ched_signatory', 'records' => 'ched_records', 'analyst' => 'ched_analyst', 'case' => 'ched_case_officer', 'admin' => 'system_administrator'] as $name => $role) {
            $this->account('ched.'.$name.'@example.test', $role, $region->id);
        }
        $this->account('other.reviewer@example.test', 'ched_reviewer', $other->id);
        $this->fixtures();
        $this->command?->info('20 fictional accounts and 9 activity plans seeded. Uploads remain quarantined until genuine scans succeed.');
    }

    private function account(string $email, string $role, ?int $regionId = null): User
    {
        $user = User::firstOrCreate(['email' => $email], ['name' => 'QA '.strtok($email, '@'), 'password' => self::PASSWORD]);
        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now(), 'region_id' => $regionId])->save();
        }
        $user->assignRole($role);

        return $user;
    }

    private function fixtures(): void
    {
        $institution = Institution::where('ched_code', 'QA-SUC')->firstOrFail();
        $official = User::where('email', 'suc.official@example.test')->firstOrFail();
        $caseOfficer = User::where('email', 'ched.case@example.test')->firstOrFail();
        foreach (['certification', 'cloca', 'saa', 'padp', 'pic_designation', 'first_aid', 'itinerary', 'parent_notice', 'orientation', 'lgu_coordination', 'acknowledgment', 'completion', 'incident', 'diagnostic', 'racp', 'official_order', 'complaint', 'clearance_application', 'clearance_correspondence', 'ceb_decision', 'reconsideration', 'monitoring'] as $code) {
            $filename = 'qa-'.$code.'.pdf';
            $relative = 'fixtures/'.$filename;
            if (! Storage::disk('local')->exists($relative)) {
                Storage::disk('local')->put($relative, Pdf::loadHTML('<h1>FICTIONAL QA EVIDENCE ONLY</h1><p>'.$code.'</p><p>QA HEI Head / QA Director</p><p>Simulated signature; no legal validity.</p>')->output());
            }
            if (! Document::where('institution_id', $institution->id)->where('original_name', $filename)->exists()) {
                $type = DocumentType::where('code', $code)->firstOrFail();
                app(UploadDocument::class)->execute($type->confidentiality === 'restricted' ? $caseOfficer : $official, $institution->id, $type->id,
                    new UploadedFile(Storage::disk('local')->path($relative), $filename, 'application/pdf', null, true), true);
            }
        }
        $relative = 'fixtures/qa-image.png';
        if (! Storage::disk('local')->exists($relative)) {
            Storage::disk('local')->put($relative, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        }
        if (! Document::where('institution_id', $institution->id)->where('original_name', 'qa-image.png')->exists()) {
            app(UploadDocument::class)->execute($official, $institution->id, DocumentType::where('code', 'supporting')->value('id'), new UploadedFile(Storage::disk('local')->path($relative), 'qa-image.png', 'image/png', null, true), false);
        }
        Storage::disk('local')->put('fixtures/qa-invalid.txt', 'This is not an accepted document type.');
        if (! Storage::disk('local')->exists('fixtures/qa-oversize.pdf')) {
            $handle = fopen(Storage::disk('local')->path('fixtures/qa-oversize.pdf'), 'wb');
            fwrite($handle, "%PDF-1.4\n");
            ftruncate($handle, 21 * 1024 * 1024);
            fclose($handle);
        }
    }
}
