<?php

namespace App\Services\Compliance;

use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Access;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class RacpPreparation
{
    /** @return array<string, mixed>|null */
    public function prepare(User $actor, int $institutionId, ?array $input): ?array
    {
        if ($input === null || $input === []) {
            return null;
        }
        $areas = config('racp.areas');
        $offices = config('racp.offices');
        $rules = ['risks' => 'nullable|array:'.implode(',', array_keys($areas)),
            'contacts' => 'nullable|array:'.implode(',', array_keys($offices)),
            'other_risks' => 'nullable|array|max:10', 'other_risks.*' => 'array:area,level,measure',
            'other_risks.*.area' => 'required|string|max:300',
            'other_risks.*.level' => 'nullable|in:low,medium,high', 'other_risks.*.measure' => 'nullable|string|max:1000',
            'other_contacts' => 'nullable|array|max:10', 'other_contacts.*' => 'array:office,person,details',
            'other_contacts.*.office' => 'required|string|max:150', 'other_contacts.*.person' => 'nullable|string|max:150',
            'other_contacts.*.details' => 'nullable|string|max:500',
            'issued_on' => 'nullable|date', 'letterhead_text' => 'nullable|string|max:1000',
            'letterhead_document_id' => 'nullable|integer', 'signatories' => 'nullable|array:prepared_by,reviewed_by,noted_by'];
        foreach ($areas as $code => $label) {
            $rules['risks.'.$code] = 'nullable|array:level,measure';
            $rules['risks.'.$code.'.level'] = 'nullable|in:low,medium,high';
            $rules['risks.'.$code.'.measure'] = 'nullable|string|max:1000';
        }
        foreach ($offices as $code => $label) {
            $rules['contacts.'.$code] = 'nullable|array:person,details';
            $rules['contacts.'.$code.'.person'] = 'nullable|string|max:150';
            $rules['contacts.'.$code.'.details'] = 'nullable|string|max:500';
        }
        foreach (['prepared_by', 'reviewed_by', 'noted_by'] as $role) {
            $rules['signatories.'.$role] = 'nullable|array:name,position,contact,email';
            foreach (['name', 'position', 'contact', 'email'] as $field) {
                $rules['signatories.'.$role.'.'.$field] = $field === 'email' ? 'nullable|email|max:255' : 'nullable|string|max:150';
            }
        }
        $validated = Validator::make(['racp' => $input], collect($rules)->mapWithKeys(fn ($rule, $key) => ['racp.'.$key => $rule])->all())->validate();
        $values = $validated['racp'];
        if (! collect(Arr::dot($values))->contains(fn ($value) => filled($value))) {
            return null;
        }
        if ($id = $values['letterhead_document_id'] ?? null) {
            $image = Document::findOrFail($id);
            abort_unless($image->institution_id === $institutionId && app(Access::class)->document($actor, $image), 403);
            abort_unless($image->scan_status === 'clean' && $image->confidentiality === 'institutional'
                && in_array($image->mime, ['image/png', 'image/jpeg'], true) && $image->size <= 2 * 1024 * 1024, 422, 'Choose a clean institutional PNG/JPG letterhead of up to 2 MB.');
        }
        $template = FormTemplate::where('code', 'racp')->whereHas('policyVersion', fn ($query) => $query->where('code', PolicyRules::CMO_CODE))->firstOrFail();

        return $values + ['assessment_version' => config('racp.version'), 'areas' => $areas, 'offices' => $offices,
            'certification' => config('racp.certification'),
            'template' => ['id' => $template->id, 'version' => $template->version,
                'policy_version_id' => $template->policy_version_id, 'annex' => 'E', 'source_page' => 24]];
    }
}
