<?php

namespace App\Services\Compliance;

use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Access;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class DiagnosticPreparation
{
    /** @return array<string, mixed>|null */
    public function prepare(User $actor, int $institutionId, ?array $input): ?array
    {
        if ($input === null || $input === []) {
            return null;
        }
        $groups = config('diagnostic.groups');
        $codes = array_keys(array_merge(...array_values($groups)));
        $rules = ['answers' => 'nullable|array:'.implode(',', $codes),
            'recommendation' => 'nullable|in:'.implode(',', array_keys(config('diagnostic.recommendations'))),
            'letterhead_text' => 'nullable|string|max:1000', 'letterhead_document_id' => 'nullable|integer',
            'signatories' => 'nullable|array:prepared_by,reviewed_by,noted_by'];
        foreach ($codes as $code) {
            $rules['answers.'.$code] = 'nullable|array:answer,remarks';
            $rules['answers.'.$code.'.answer'] = 'nullable|in:yes,no';
            $rules['answers.'.$code.'.remarks'] = 'nullable|string|max:1000';
        }
        foreach (['prepared_by', 'reviewed_by', 'noted_by'] as $role) {
            $rules['signatories.'.$role] = 'nullable|array:name,position,contact,email';
            foreach (['name', 'position', 'contact', 'email'] as $field) {
                $rules['signatories.'.$role.'.'.$field] = $field === 'email' ? 'nullable|email|max:255' : 'nullable|string|max:150';
            }
        }
        $validated = Validator::make(['diagnostic' => $input], collect($rules)->mapWithKeys(fn ($rule, $key) => ['diagnostic.'.$key => $rule])->all())->validate();
        $values = $validated['diagnostic'];
        if (! collect(Arr::dot($values))->contains(fn ($value) => filled($value))) {
            return null;
        }
        if ($id = $values['letterhead_document_id'] ?? null) {
            $image = Document::findOrFail($id);
            abort_unless($image->institution_id === $institutionId && app(Access::class)->document($actor, $image), 403);
            abort_unless($image->scan_status === 'clean' && $image->confidentiality === 'institutional'
                && in_array($image->mime, ['image/png', 'image/jpeg'], true) && $image->size <= 2 * 1024 * 1024, 422, 'Choose a clean institutional PNG/JPG letterhead of up to 2 MB.');
        }
        $template = FormTemplate::where('code', 'diagnostic')->whereHas('policyVersion', fn ($query) => $query->where('code', PolicyRules::CMO_CODE))->firstOrFail();

        return $values + ['criteria_version' => config('diagnostic.version'), 'groups' => $groups,
            'recommendations' => config('diagnostic.recommendations'),
            'template' => ['id' => $template->id, 'version' => $template->version,
                'policy_version_id' => $template->policy_version_id, 'annex' => 'D', 'source_page' => 23]];
    }
}
