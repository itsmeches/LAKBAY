<?php

namespace App\Actions\Submissions;

use App\Models\ClassificationRuleSet;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProposeClassificationRuleSet
{
    public function execute(User $actor, ClassificationRuleSet $source, array $data): ClassificationRuleSet
    {
        abort_unless($actor->is_active && $actor->hasVerifiedEmail() && $actor->can('panel.ched') && $actor->can('policy.manage') && $actor->region_id, 403);
        Validator::make($data, ['code' => 'required|string|max:255', 'decision_source' => 'required|string|max:4000',
            'rules' => 'required|array', 'rules.materiality' => 'required|array',
            'rules.materiality.*.default_outcome' => 'required|in:category_2_and_rcd,rcd_only,record_only,new_preparation_required',
            'rules.materiality.*.contextual_outcome' => 'required|in:category_2_and_rcd,rcd_only,record_only,new_preparation_required',
            'rules.materiality.*.source_reference' => 'required|string', 'rules.materiality.*.decision_source' => 'required|string',
            'rules.materiality.*.needs_ched_decision' => 'required|boolean', 'rules.materiality.*.interpretation' => 'required|string',
            'rules.timing.rcd_anchor' => 'required|string', 'rules.timing.source_reference' => 'required|string',
            'rules.category_3_escalation.triggers' => 'required|array', 'rules.category_3_escalation.source_reference' => 'required|string'])->validate();

        return DB::transaction(function () use ($actor, $source, $data): ClassificationRuleSet {
            ClassificationRuleSet::whereKey($source->id)->lockForUpdate()->firstOrFail();
            $existing = ClassificationRuleSet::where('code', $data['code'])->first();
            if ($existing) {
                if ($existing->supersedes_id !== $source->id || $existing->rules !== $data['rules'] || $existing->decision_source !== $data['decision_source']) {
                    throw ValidationException::withMessages(['code' => 'This code already identifies a different immutable proposal.']);
                }

                return $existing;
            }
            $proposal = ClassificationRuleSet::create(['policy_version_id' => $source->policy_version_id, 'supersedes_id' => $source->id,
                'code' => $data['code'], 'status' => 'proposed', 'rules' => $data['rules'], 'source' => $source->source,
                'decision_source' => $data['decision_source'], 'created_by' => $actor->id]);
            Audit::record($actor, 'classification.rules.proposed', $proposal, ['supersedes_id' => $source->id]);

            return $proposal;
        });
    }
}
