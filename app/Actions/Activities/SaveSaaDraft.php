<?php

namespace App\Actions\Activities;

use App\Models\AcademicPeriod;
use App\Models\AccountabilityAffidavit;
use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Compliance\PolicyRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaveSaaDraft
{
    public function execute(User $actor, array $data, ?AccountabilityAffidavit $draft = null): AccountabilityAffidavit
    {
        $institutionId = $draft?->institution_id ?? (int) ($data['institution_id'] ?? 0);
        app(Access::class)->authorize($actor, $institutionId, 'activities.manage');
        $keys = ['name', 'position', 'office_address', 'citizenship', 'semester', 'academic_year', 'letterhead_text',
            'letterhead_document_id', 'execution_date', 'execution_place', 'notarial_date', 'notarial_place',
            'notary_name', 'identity_description', 'doc_number', 'page_number', 'book_number', 'series'];
        $rules = ['title' => 'required|string|max:255', 'details' => 'nullable|array:'.implode(',', $keys)];
        foreach ($keys as $key) {
            $rules['details.'.$key] = match ($key) {
                'execution_date', 'notarial_date' => 'nullable|date',
                'letterhead_document_id' => 'nullable|integer',
                'letterhead_text' => 'nullable|string|max:1000',
                'office_address' => 'nullable|string|max:300',
                default => 'nullable|string|max:150',
            };
        }
        Validator::make($data, $rules)->validate();
        if (! $draft) {
            Validator::make($data, ['academic_period_id' => 'required|integer|exists:academic_periods,id'])->validate();
            abort_unless(AcademicPeriod::findOrFail($data['academic_period_id'])->institution_id === $institutionId, 403);
        } else {
            abort_if(isset($data['institution_id']) && (int) $data['institution_id'] !== $institutionId, 403);
            abort_if(isset($data['academic_period_id']) && (int) $data['academic_period_id'] !== $draft->academic_period_id, 422, 'Create a separate SAA draft for another academic period.');
        }
        if ($id = data_get($data, 'details.letterhead_document_id')) {
            $image = Document::findOrFail($id);
            abort_unless($image->institution_id === $institutionId && app(Access::class)->document($actor, $image), 403);
            abort_unless($image->scan_status === 'clean' && $image->confidentiality === 'institutional'
                && in_array($image->mime, ['image/png', 'image/jpeg'], true) && $image->size <= 2 * 1024 * 1024, 422, 'Choose a clean institutional PNG/JPG letterhead of up to 2 MB.');
        }

        return DB::transaction(function () use ($actor, $data, $draft, $institutionId): AccountabilityAffidavit {
            $values = collect($data)->only(['title', 'details'])->all() + ['updated_by' => $actor->id];
            if ($draft) {
                $record = AccountabilityAffidavit::whereKey($draft->id)->lockForUpdate()->firstOrFail();
                $record->update($values + ['revision' => $record->revision + 1]);
            } else {
                $template = FormTemplate::where('code', 'saa')->whereHas('policyVersion', fn ($query) => $query->where('code', PolicyRules::CMO_CODE))->firstOrFail();
                $record = AccountabilityAffidavit::create($values + ['institution_id' => $institutionId,
                    'academic_period_id' => (int) $data['academic_period_id'], 'form_template_id' => $template->id, 'created_by' => $actor->id]);
            }
            Audit::record($actor, $draft ? 'saa.draft_revised' : 'saa.draft_created', $record);

            return $record;
        });
    }
}
