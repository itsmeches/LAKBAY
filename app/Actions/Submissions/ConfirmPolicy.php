<?php

namespace App\Actions\Submissions;

use App\Models\PolicyVersion;
use App\Models\User;
use App\Services\Audit;
use App\Services\Compliance\PolicyRules;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConfirmPolicy
{
    public function execute(User $actor, PolicyVersion $policy, array $data): PolicyVersion
    {
        abort_unless($actor->is_active && $actor->hasVerifiedEmail() && $actor->can('policy.manage') && $actor->can('panel.ched') && $actor->region_id, 403);
        $cmo = PolicyRules::isCmo($policy);
        Validator::make($data, ['confirmation_reference' => 'required|string|max:255', 'confirmed_by_ched' => 'accepted',
            'publication_on' => $cmo ? 'required|date' : 'nullable|date',
            'publication_reference' => $cmo ? 'required|string|max:255' : 'nullable|string',
            'effective_on' => $cmo ? 'nullable|date' : 'required|date'])->validate();

        return DB::transaction(function () use ($actor, $policy, $data, $cmo) {
            $p = PolicyVersion::whereKey($policy->id)->lockForUpdate()->firstOrFail();
            abort_unless($p->status !== 'confirmed' && $p->requirements()->exists(), 422);
            $publication = empty($data['publication_on']) ? null : CarbonImmutable::parse($data['publication_on'], config('lakbay.timezone'))->startOfDay();
            abort_if($publication && $publication->gt(CarbonImmutable::now(config('lakbay.timezone'))->startOfDay()), 422, 'Publication cannot be in the future.');
            abort_if($cmo && $p->issued_on && $publication->toDateString() < $p->issued_on->toDateString(), 422, 'Publication cannot precede issuance.');
            $p->update(['status' => 'confirmed', 'publication_on' => $publication,
                'publication_reference' => $data['publication_reference'] ?? null,
                'effective_on' => $cmo ? $publication->addDays(15)->toDateString() : $data['effective_on'],
                'confirmation_reference' => $data['confirmation_reference'], 'confirmed_by' => $actor->id, 'confirmed_at' => now()]);
            Audit::record($actor, 'policy.confirmed', $p);

            return $p;
        });
    }
}
