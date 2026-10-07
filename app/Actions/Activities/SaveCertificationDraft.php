<?php

namespace App\Actions\Activities;

use App\Models\AcademicPeriod;
use App\Models\CertificationDraft;
use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Compliance\PolicyRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveCertificationDraft
{
    public function execute(User $actor, array $data, ?CertificationDraft $draft = null): CertificationDraft
    {
        $institutionId = $draft?->institution_id ?? (int) ($data['institution_id'] ?? 0);
        app(Access::class)->authorize($actor, $institutionId, 'activities.manage');
        $definition = $draft?->details['definition'] ?? config('certification');
        $rules = ['title' => 'required|string|max:255', 'details' => 'nullable|array:items,official,position,issued_on,issued_at,letterhead_text,letterhead_document_id',
            'details.items' => 'nullable|array:'.implode(',', array_keys($definition['rows'])),
            'details.official' => 'nullable|string|max:150', 'details.position' => 'nullable|string|max:150',
            'details.issued_on' => 'nullable|date', 'details.issued_at' => 'nullable|string|max:300',
            'details.letterhead_text' => 'nullable|string|max:1000', 'details.letterhead_document_id' => 'nullable|integer'];
        foreach ($definition['rows'] as $code => $row) {
            $path = 'details.items.'.$code;
            $rules[$path] = 'nullable|array:status,reason,supporting';
            $rules[$path.'.status'] = 'nullable|in:attested,pending,not_applicable';
            $rules[$path.'.reason'] = 'required_if:'.$path.'.status,not_applicable|nullable|string|max:500';
            $rules[$path.'.supporting'] = $row['supporting'] ? 'nullable|array:'.implode(',', array_keys($row['supporting'])) : 'nullable|array|size:0';
            foreach ($row['supporting'] as $key => $label) {
                $rules[$path.'.supporting.'.$key] = 'nullable|boolean';
            }
        }
        Validator::make($data, $rules)->validate();
        foreach ($data['details']['items'] ?? [] as $code => $item) {
            if (($item['status'] ?? null) === 'not_applicable' && collect($item['supporting'] ?? [])->contains(fn ($checked) => (bool) $checked)) {
                throw ValidationException::withMessages(['details.items.'.$code.'.status' => 'Clear supporting-document checks before marking this requirement not applicable.']);
            }
        }
        if (! $draft) {
            Validator::make($data, ['academic_period_id' => 'required|integer|exists:academic_periods,id'])->validate();
            abort_unless(AcademicPeriod::findOrFail($data['academic_period_id'])->institution_id === $institutionId, 403);
        } else {
            abort_if(isset($data['institution_id']) && (int) $data['institution_id'] !== $institutionId, 403);
            abort_if(isset($data['academic_period_id']) && (int) $data['academic_period_id'] !== $draft->academic_period_id, 422, 'Create another certification draft for a different academic period.');
        }
        if ($id = data_get($data, 'details.letterhead_document_id')) {
            $image = Document::findOrFail($id);
            abort_unless($image->institution_id === $institutionId && app(Access::class)->document($actor, $image), 403);
            abort_unless($image->scan_status === 'clean' && $image->confidentiality === 'institutional'
                && in_array($image->mime, ['image/png', 'image/jpeg'], true) && $image->size <= 2 * 1024 * 1024, 422, 'Choose a clean institutional PNG/JPG letterhead of up to 2 MB.');
        }

        return DB::transaction(function () use ($actor, $data, $draft, $institutionId, $definition): CertificationDraft {
            $values = ['title' => $data['title'], 'details' => ($data['details'] ?? []) + ['definition' => $definition], 'updated_by' => $actor->id];
            if ($draft) {
                $record = CertificationDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
                $record->update($values + ['revision' => $record->revision + 1]);
            } else {
                $template = FormTemplate::where('code', 'certification')->whereHas('policyVersion', fn ($query) => $query->where('code', PolicyRules::CMO_CODE))->firstOrFail();
                $record = CertificationDraft::create($values + ['institution_id' => $institutionId,
                    'academic_period_id' => (int) $data['academic_period_id'], 'form_template_id' => $template->id, 'created_by' => $actor->id]);
            }
            Audit::record($actor, $draft ? 'certification.draft_revised' : 'certification.draft_created', $record);

            return $record;
        });
    }
}
