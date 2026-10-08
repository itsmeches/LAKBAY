<?php

namespace App\Actions\Submissions;

use App\Models\Activity;
use App\Models\PolicyVersion;
use App\Models\Submission;
use App\Models\SubmissionActivity;
use App\Services\Compliance\PolicyRules;
use Illuminate\Support\Facades\DB;

class CorrectSaaPolicy
{
    /** @return array<string, int|string|bool> */
    public function plan(PolicyVersion $source): array
    {
        abort_unless(PolicyRules::isCmo($source), 422, 'Select the CMO 24 baseline.');
        $code = $source->code.'-SAA-R1';
        $excluded = $source->requirements()->where('section_row', 10)->where('category', 3)->count();
        $exists = PolicyVersion::where('code', $code)->exists();

        return [
            'source_policy_id' => $source->id, 'successor_code' => $code,
            'new_policy_versions' => $excluded && ! $exists ? 1 : 0,
            'new_policy_requirements' => $excluded && ! $exists ? $source->requirements()->count() - $excluded : 0,
            'new_form_templates' => $excluded && ! $exists ? $source->formTemplates()->count() : 0,
            'ordinary_category_3_drafts_recomputed_on_open' => Activity::whereHas('latestVersion', fn ($q) => $q->where('category', 3)->where('requirement_category', 3))
                ->whereDoesntHave('versions', fn ($q) => $q->whereIn('id', SubmissionActivity::select('activity_version_id')))->count(),
            'historical_submissions_preserved' => Submission::where('policy_version_id', $source->id)->count(),
            'existing_rows_updated' => 0, 'existing_rows_deleted' => 0,
        ];
    }

    public function execute(PolicyVersion $source): PolicyVersion
    {
        return DB::transaction(function () use ($source): PolicyVersion {
            $source = PolicyVersion::whereKey($source->id)->lockForUpdate()->firstOrFail();
            $plan = $this->plan($source);
            if ($existing = PolicyVersion::where('code', $plan['successor_code'])->first()) {
                return $existing;
            }
            if (! $plan['new_policy_versions']) {
                return $source;
            }
            $policy = $source->replicate();
            $policy->fill(['code' => $plan['successor_code'], 'title' => $source->title.' — SAA category correction R1',
                'status' => 'candidate', 'publication_on' => null, 'publication_reference' => null, 'effective_on' => null,
                'confirmed_by' => null, 'confirmed_at' => null, 'confirmation_reference' => null,
                'parameters' => array_replace($source->parameters ?? [], ['supersedes_policy_version_id' => $source->id,
                    'correction_reference' => 'CMO 24 §14 row 10, PDF p.9: SAA Categories 1–2 only'])]);
            $policy->save();
            foreach ($source->requirements()->get() as $rule) {
                if ($rule->section_row === 10 && $rule->category === 3) {
                    continue;
                }
                $copy = $rule->replicate();
                $copy->policy_version_id = $policy->id;
                $copy->save();
            }
            foreach ($source->formTemplates()->get() as $template) {
                $copy = $template->replicate();
                $copy->policy_version_id = $policy->id;
                $copy->save();
            }

            return $policy;
        });
    }
}
