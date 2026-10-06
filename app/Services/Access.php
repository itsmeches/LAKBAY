<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Access
{
    public function owns(User $user, int $institutionId, bool $submit = false): bool
    {
        return $user->is_active && $user->hasVerifiedEmail() && $user->institutions()->where('institutions.id', $institutionId)->where('institutions.is_active', true)->whereNotNull('institution_user.verified_at')->when($submit, fn ($q) => $q->where('institution_user.is_authorized', true))->exists();
    }

    public function regional(User $user, int $institutionId): bool
    {
        return $user->is_active && $user->hasVerifiedEmail() && $user->region_id && $user->can('panel.ched') && Institution::whereKey($institutionId)->where('region_id', $user->region_id)->exists();
    }

    public function authorize(User $user, int $institutionId, string $permission, bool $submit = false, bool $restricted = false): void
    {
        abort_unless($user->can($permission) && ($restricted ? $this->regional($user, $institutionId) : ($this->regional($user, $institutionId) || $this->owns($user, $institutionId, $submit))), 403);
    }

    public function scope(Builder $query, User $user, string $column = 'institution_id'): Builder
    {
        if (! $user->is_active || ! $user->hasVerifiedEmail()) {
            return $query->whereRaw('1=0');
        }
        if ($user->can('panel.ched') && $user->region_id) {
            return $query->whereIn($column, Institution::where('region_id', $user->region_id)->select('id'));
        }

        return $query->whereIn($column, $user->institutions()->whereNotNull('institution_user.verified_at')->where('institutions.is_active', true)->select('institutions.id'));
    }

    public function document(User $user, Document $document): bool
    {
        if ($document->confidentiality === 'restricted') {
            return $user->can('cases.sensitive') && $this->regional($user, $document->institution_id);
        }
        if ($document->confidentiality === 'sensitive' && ! $this->owns($user, $document->institution_id)) {
            return $user->can('incidents.view') && $this->regional($user, $document->institution_id);
        }

        return $user->can('records.view') && ($this->regional($user, $document->institution_id) || $this->owns($user, $document->institution_id));
    }
}
