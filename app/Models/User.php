<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, MustVerifyEmail
{
    use HasFactory, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, Notifiable;

    protected $attributes = ['is_active' => true];

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean', 'app_authentication_secret' => 'encrypted', 'app_authentication_recovery_codes' => 'encrypted:array'];
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class)->withPivot(['position', 'is_authorized', 'verified_at'])->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active || ! $this->hasVerifiedEmail()) {
            return false;
        }

        return match ($panel->getId()) {
            'ched' => $this->region_id !== null && $this->can('panel.ched'),
            'hei' => $this->can('panel.hei') && $this->institutions()->whereNotNull('institution_user.verified_at')->where('institutions.is_active', true)->exists(),
            default => false
        };
    }
}
