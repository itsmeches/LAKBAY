<?php

namespace Tests\Feature;

use App\Filament\Hei\Resources\Pages\ListSubmission;
use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PanelQaTest extends TestCase
{
    use RefreshDatabase;

    private User $hei;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('hei'));
        $this->hei = User::factory()->create(['password' => 'QA-password-only!2026', 'app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->hei->assignRole('hei_submitter');
        $institution = Institution::create(['region_id' => Region::first()->id, 'ched_code' => 'PANEL-QA', 'name' => 'Panel QA HEI', 'institution_type' => 'private']);
        $this->hei->institutions()->attach($institution->id, ['position' => 'QA official', 'verified_at' => now(), 'is_authorized' => true]);
    }

    public static function invalidCredentials(): array
    {
        return ['empty' => ['', '', ['email', 'password']], 'unknown' => ['unknown@example.test', 'QA-password-only!2026', ['email']], 'wrong password' => ['existing', 'wrong-password-only!2026', ['email']]];
    }

    #[DataProvider('invalidCredentials')]
    public function test_invalid_login_never_creates_a_session(string $email, string $password, array $errors): void
    {
        Livewire::test(Login::class)->fillForm(['email' => $email === 'existing' ? $this->hei->email : $email, 'password' => $password])->call('authenticate')->assertHasFormErrors($errors);
        $this->assertGuest();
    }

    public function test_profile_saves_own_name_without_changing_authority(): void
    {
        $this->actingAs($this->hei);
        Livewire::test(EditProfile::class)->fillForm(['name' => 'QA renamed official'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('QA renamed official', $this->hei->fresh()->name);
        $this->assertTrue($this->hei->fresh()->hasRole('hei_submitter'));
        $this->assertNull($this->hei->fresh()->region_id);
    }

    public function test_profile_rejects_empty_name_and_invalid_email(): void
    {
        $this->actingAs($this->hei);
        $original = $this->hei->name;
        Livewire::test(EditProfile::class)->fillForm(['name' => '', 'email' => 'bad-email'])->call('save')->assertHasFormErrors(['name', 'email']);
        $this->assertSame($original, $this->hei->fresh()->name);
    }

    public function test_password_change_requires_matching_confirmation(): void
    {
        $this->actingAs($this->hei);
        Livewire::test(EditProfile::class)->fillForm(['password' => 'QA-new-password!2026', 'passwordConfirmation' => 'different-password!2026'])->call('save')->assertHasFormErrors(['password']);
        $this->assertTrue(Hash::check('QA-password-only!2026', $this->hei->fresh()->password));
    }

    public function test_logout_removes_authenticated_access(): void
    {
        $this->actingAs($this->hei);
        $this->post(route('filament.hei.auth.logout'))->assertRedirect();
        $this->assertGuest();
        $this->get('/hei/activities')->assertRedirect('/hei/login');
    }

    public function test_hei_submission_list_has_no_direct_create_action(): void
    {
        $this->actingAs($this->hei);
        Livewire::test(ListSubmission::class)
            ->assertActionDoesNotExist('create')
            ->assertDontSee('New submission');
    }

    public function test_ched_administrator_submission_list_has_no_direct_create_action(): void
    {
        $administrator = User::factory()->create(['region_id' => Region::first()->id]);
        $administrator->assignRole('system_administrator');
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        $this->actingAs($administrator);
        Livewire::test(\App\Filament\Ched\Resources\Pages\ListSubmission::class)
            ->assertActionDoesNotExist('create')
            ->assertDontSee('New submission');
    }
}
