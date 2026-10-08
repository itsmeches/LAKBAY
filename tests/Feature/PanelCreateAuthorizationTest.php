<?php

namespace Tests\Feature;

use App\Filament\Ched\Resources\CaseRecordResource;
use App\Filament\Ched\Resources\DocumentResource;
use App\Filament\Ched\Resources\Pages\ListCaseRecord;
use App\Filament\Ched\Resources\Pages\ListDocument;
use App\Filament\Ched\Resources\Pages\ListTechnicalAssistanceLog;
use App\Models\Region;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelCreateAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_ched_roles_can_open_their_creation_forms(): void
    {
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        foreach (['ched_records' => ListDocument::class, 'ched_monitor' => ListTechnicalAssistanceLog::class, 'ched_case_officer' => ListCaseRecord::class] as $role => $page) {
            $user = User::factory()->create(['region_id' => Region::first()->id, 'app_authentication_secret' => 'TESTSECRET']);
            $user->assignRole($role);
            $this->actingAs($user);
            Livewire::test($page)->assertActionVisible('create')->mountAction('create')->assertActionMounted('create');
        }
    }

    public function test_creation_remains_denied_without_role_verification_or_panel_membership(): void
    {
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('ched'));
        $user = User::factory()->create(['region_id' => Region::first()->id]);
        $user->assignRole('ched_analyst');
        $this->actingAs($user);
        $this->assertFalse(DocumentResource::getCreateAuthorizationResponse()->allowed());
        $user->assignRole('ched_records');
        $user->forceFill(['email_verified_at' => null])->save();
        $this->assertFalse(DocumentResource::getCreateAuthorizationResponse()->allowed());
        $user->forceFill(['email_verified_at' => now(), 'region_id' => null])->save();
        $this->assertFalse(DocumentResource::getCreateAuthorizationResponse()->allowed());
        $user->forceFill(['region_id' => Region::first()->id, 'is_active' => false])->save();
        $this->assertFalse(DocumentResource::getCreateAuthorizationResponse()->allowed());
        $user->forceFill(['is_active' => true])->save();
        $user->syncRoles(['system_administrator']);
        $this->assertFalse(CaseRecordResource::getCreateAuthorizationResponse()->allowed());
    }
}
