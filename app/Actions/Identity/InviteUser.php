<?php

namespace App\Actions\Identity;

use App\Models\Institution;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotice;
use App\Services\Audit;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InviteUser
{
    public function execute(User $actor, array $data): Invitation
    {
        abort_unless($actor->is_active && $actor->hasVerifiedEmail() && $actor->can('users.invite') && $actor->region_id, 403);
        Validator::make($data, ['name' => 'required|string', 'email' => 'required|email|unique:users,email', 'role' => 'required|in:hei_preparer,hei_submitter,ched_records,ched_reviewer,ched_signatory,ched_monitor,ched_case_officer,ched_analyst,system_administrator', 'position' => 'required|string'])->validate();
        $isHei = str_starts_with($data['role'], 'hei_');
        if ($isHei) {
            $i = Institution::findOrFail($data['institution_id'] ?? 0);
            abort_unless($i->region_id === $actor->region_id, 403);
        }
        $token = Str::random(64);
        $i = Invitation::create(collect($data)->only(['name', 'email', 'role', 'position'])->all() + ['institution_id' => $isHei ? $data['institution_id'] : null, 'region_id' => $isHei ? null : $actor->region_id, 'token_hash' => hash('sha256', $token), 'invited_by' => $actor->id, 'expires_at' => now()->addDays(3)]);
        Notification::route('mail', $i->email)->notify(new InvitationNotice($token));
        Audit::record($actor, 'user.invited', $i, ['role' => $i->role]);

        return $i;
    }
}
