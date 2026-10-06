<?php

namespace App\Actions\Operations;

use App\Models\Activity;
use App\Models\ActivityVersion;
use App\Models\CaseRecord;
use App\Models\ClearanceRequest;
use App\Models\Document;
use App\Models\InstitutionalRestriction;
use App\Models\MonitoringRecord;
use App\Models\TechnicalAssistanceLog;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RecordOperationalRecord
{
    public function execute(User $actor, string $type, array $data)
    {
        [$class,$permission,$actorField,$rules] = match ($type) {
            'monitoring' => [MonitoringRecord::class, 'monitoring.manage', 'monitored_by', ['period' => 'required|string', 'monitored_on' => 'required|date', 'type' => 'required|in:desk,virtual,on_site,incident_based', 'respondent' => 'required|string', 'overall_rating' => 'required|in:compliant,non_compliant', 'findings' => 'required|string', 'good_practices' => 'nullable|string', 'suggestions' => 'nullable|string']],
            'assistance' => [TechnicalAssistanceLog::class, 'monitoring.manage', 'provided_by', ['type' => 'required|string', 'provided_on' => 'required|date', 'summary' => 'required|string', 'follow_up' => 'nullable|string']],
            'case' => [CaseRecord::class, 'cases.sensitive', 'created_by', ['reference' => 'required|string|unique:case_records,reference', 'type' => 'required|in:complaint,investigation', 'summary' => 'required|string', 'status' => 'required|in:received,referred,investigating', 'opened_at' => 'required|date']],
            'clearance' => [ClearanceRequest::class, 'cases.sensitive', 'created_by', ['activity_version_id' => 'required|integer', 'case_record_id' => 'required|integer', 'justification' => 'required|string', 'endorsement_reference' => 'nullable|string', 'osds_reference' => 'nullable|string']],
            'restriction' => [InstitutionalRestriction::class, 'cases.sensitive', 'created_by', ['type' => 'required|in:suspension,clearance_required,corrective_order', 'effective_from' => 'required|date', 'effective_until' => 'nullable|date|after_or_equal:effective_from', 'issuing_authority' => 'required|string', 'document_id' => 'required|integer']],
            default => throw new \InvalidArgumentException('Unknown operational record.')
        };
        Validator::make($data, ['institution_id' => 'required|integer', 'activity_id' => 'nullable|integer'] + $rules)->validate();
        $id = (int) $data['institution_id'];
        app(Access::class)->authorize($actor, $id, $permission, false, true);
        if (! empty($data['activity_id'])) {
            abort_unless(Activity::findOrFail($data['activity_id'])->institution_id === $id, 403);
        }
        if ($type === 'clearance') {
            $v = ActivityVersion::findOrFail($data['activity_version_id']);
            $case = CaseRecord::findOrFail($data['case_record_id']);
            abort_unless($v->activity->institution_id === $id && $case->institution_id === $id, 403);
            if ($case->type !== 'investigation' || $case->status !== 'investigating') {
                throw ValidationException::withMessages(['case' => 'An explicitly recorded ongoing investigation is required.']);
            }
        }
        if ($type === 'restriction') {
            $doc = Document::findOrFail($data['document_id']);
            abort_unless($doc->institution_id === $id && $doc->scan_status === 'clean' && $doc->is_signed && $doc->documentType->code === 'official_order', 422);
        }

        return DB::transaction(function () use ($actor, $class, $actorField, $rules, $data, $type) {
            $values = collect($data)->only(array_merge(array_keys($rules), ['institution_id', 'activity_id']))->all();
            if ($type === 'case') {
                $values['jurisdiction_region_id'] = $actor->region_id;
            }
            $r = $class::create($values + [$actorField => $actor->id]);
            if ($type === 'monitoring') {
                Validator::make($data, ['items' => 'required|array|size:14', 'items.*.criterion_code' => 'required|distinct', 'items.*.compliance' => 'required|in:yes,no', 'items.*.verification' => 'required|string'])->validate();
                foreach ($data['items'] as $item) {
                    $criterion = config('monitoring.criteria.'.$item['criterion_code']);
                    abort_unless($criterion, 422);
                    $r->items()->create(['criterion_code' => $item['criterion_code'], 'criterion' => $criterion, 'compliance' => $item['compliance'], 'verification' => $item['verification'], 'remarks' => $item['remarks'] ?? null]);
                }
            }
            Audit::record($actor, $type.'.recorded', $r);

            return $r;
        });
    }
}
