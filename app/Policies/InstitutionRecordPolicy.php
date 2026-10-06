<?php

namespace App\Policies;

use App\Models\CaseRecord;
use App\Models\ClearanceRequest;
use App\Models\Document;
use App\Models\IncidentReport;
use App\Models\Institution;
use App\Models\InstitutionalRestriction;
use App\Models\User;
use App\Services\Access;

class InstitutionRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasVerifiedEmail() && $user->can('records.view');
    }

    public function view(User $user, $record): bool
    {
        $id = $record instanceof Institution ? $record->id : $record->institution_id;
        if ($record instanceof Document) {
            return app(Access::class)->document($user, $record);
        }
        if ($record instanceof CaseRecord || $record instanceof ClearanceRequest || $record instanceof InstitutionalRestriction) {
            return $user->can('cases.sensitive') && app(Access::class)->regional($user, $id);
        }
        if ($record instanceof IncidentReport && ! app(Access::class)->owns($user, $id)) {
            return $user->can('incidents.view') && app(Access::class)->regional($user, $id);
        }

        return app(Access::class)->regional($user, $id) || app(Access::class)->owns($user, $id);
    }

    public function create(User $user): bool
    {
        return $user->can('activities.manage') || $user->can('institutions.manage');
    }

    public function update(User $user, $record): bool
    {
        return false;
    }

    public function delete(User $user, $record): bool
    {
        return false;
    }
}
