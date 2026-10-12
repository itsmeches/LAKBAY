<?php

namespace App\Actions\Operations;

use App\Models\Activity;
use App\Models\ActivityVersion;
use App\Models\CaseEvent;
use App\Models\CaseRecord;
use App\Models\ClearanceRequest;
use App\Models\Document;
use App\Models\InstitutionalRestriction;
use App\Models\MonitoringRecord;
use App\Models\TechnicalAssistanceLog;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Compliance\DeadlineCalculator;
use App\Services\Compliance\PolicyRules;
use App\Services\Reporting\MonitoringBuilder;
use Carbon\CarbonImmutable;
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
        $rules += match ($type) {
            'case' => ['source_document_id' => 'required|integer|exists:documents,id'],
            'clearance' => ['application_received_at' => 'required|date', 'application_document_id' => 'required|integer|exists:documents,id'],
            'monitoring' => ['annex_metadata' => 'sometimes|array:respondent_position,prepared_by,prepared_position,reviewed_by,reviewed_position,noted_by,noted_position,hei_conforme,hei_conforme_date,other_findings',
                'annex_metadata.*' => 'nullable|string|max:2000',
                'annex_metadata.hei_conforme_date' => 'nullable|date_format:Y-m-d',
                'items' => 'required|array|size:14', 'items.*.criterion_code' => 'required|distinct|in:1,2,3,4,5,6,7,8,9,10,11,12,13,14',
                'items.*.compliance' => 'required|in:yes,no', 'items.*.verification' => 'required|string|max:2000',
                'items.*.remarks' => 'nullable|string|max:2000'],
            default => [],
        };
        Validator::make($data, ['institution_id' => 'required|integer', 'activity_id' => 'nullable|integer'] + $rules)->validate();
        if ($type === 'monitoring') {
            Validator::make($data, ['period' => 'string|max:255', 'respondent' => 'string|max:255', 'findings' => 'string|max:2000',
                'good_practices' => 'nullable|string|max:2000', 'suggestions' => 'nullable|string|max:2000',
                'annex_metadata.respondent_position' => 'nullable|string|max:255',
                'annex_metadata.prepared_by' => 'nullable|string|max:255', 'annex_metadata.prepared_position' => 'nullable|string|max:255',
                'annex_metadata.reviewed_by' => 'nullable|string|max:255', 'annex_metadata.reviewed_position' => 'nullable|string|max:255',
                'annex_metadata.noted_by' => 'nullable|string|max:255', 'annex_metadata.noted_position' => 'nullable|string|max:255',
                'annex_metadata.hei_conforme' => 'nullable|string|max:255'])->validate();
        }
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
            $doc = Document::findOrFail($data['application_document_id']);
            abort_unless($doc->institution_id === $id && $doc->scan_status === 'clean' && $doc->is_signed && $doc->documentType->code === 'clearance_application', 422);
            $policy = app(PolicyRules::class)->forActivity($v->activity);
            $parameters = app(PolicyRules::class)->parameters($policy);
            $due = app(DeadlineCalculator::class)->calculate('calendar_days_before', (int) $parameters['clearance_days_before'], $v->starts_at, config('lakbay.holiday_calendar_version'));
            $rules += ['policy_version_id' => 'nullable|integer', 'application_due_at' => 'nullable|date', 'application_is_late' => 'nullable|boolean', 'deadline_parameters' => 'array', 'routing_history' => 'array'];
            $data = array_replace($data, ['policy_version_id' => $policy?->id, 'application_due_at' => $due,
                'application_is_late' => CarbonImmutable::parse($data['application_received_at'], config('lakbay.timezone'))->utc()->gt($due),
                'deadline_parameters' => ['type' => 'calendar_days_before', 'amount' => $parameters['clearance_days_before'], 'unit_status' => 'calendar_provisional', 'policy_code' => $policy?->code], 'routing_history' => []]);
            $data['application_received_at'] = CarbonImmutable::parse($data['application_received_at'], config('lakbay.timezone'))->utc();
            abort_if($data['application_received_at']->isFuture(), 422, 'Application receipt cannot be in the future (Asia/Manila).');
        }
        if ($type === 'case') {
            $doc = Document::findOrFail($data['source_document_id']);
            $code = $data['type'] === 'complaint' ? 'complaint' : 'official_order';
            abort_unless($doc->institution_id === $id && $doc->scan_status === 'clean' && $doc->is_signed && $doc->mime === 'application/pdf' && $doc->confidentiality === 'restricted' && $doc->documentType->code === $code && app(Access::class)->document($actor, $doc), 422, 'Record clean signed restricted complaint or authoritative investigation evidence.');
            abort_if($data['type'] === 'complaint' && $data['status'] === 'investigating', 422, 'A complaint does not establish an authoritative ongoing investigation.');
            abort_if($data['status'] === 'referred', 422, 'Create the received record, then record the referral with its jurisdiction and evidence.');
        }
        if ($type === 'restriction') {
            $doc = Document::findOrFail($data['document_id']);
            abort_unless($doc->institution_id === $id && $doc->scan_status === 'clean' && $doc->is_signed && $doc->mime === 'application/pdf' && $doc->confidentiality === 'restricted' && $doc->documentType->code === 'official_order' && app(Access::class)->document($actor, $doc), 422);
        }

        return DB::transaction(function () use ($actor, $class, $actorField, $rules, $data, $type) {
            $values = collect($data)->only(array_merge(array_keys($rules), ['institution_id', 'activity_id']))->except('items')->all();
            if ($type === 'clearance') {
                ActivityVersion::whereKey($data['activity_version_id'])->lockForUpdate()->firstOrFail();
                abort_if(ClearanceRequest::where('activity_version_id', $data['activity_version_id'])->where('case_record_id', $data['case_record_id'])->exists(), 409, 'This revision and investigation already have a clearance application. Record routing or reconsideration on that request.');
                unset($values['activity_id']);
            }
            if ($type === 'case') {
                $values['jurisdiction_region_id'] = $actor->region_id;
            }
            if ($type === 'monitoring') {
                $values['criteria_version'] = 'CMO24-2026-Annex-M-renderer-1';
                $values['annex_metadata'] = array_replace($data['annex_metadata'] ?? [], ['region_id' => $actor->region_id, 'criteria' => collect(config('monitoring.annex_m'))->map(fn ($row) => $row['criterion'])->all()]);
                $values['annex_m'] = app(MonitoringBuilder::class)->snapshot($actor, $data);
            }
            $r = $class::create($values + [$actorField => $actor->id]);
            if ($type === 'case') {
                CaseEvent::create(['case_record_id' => $r->id, 'type' => 'received', 'occurred_at' => $r->opened_at,
                    'notes' => 'Source evidence recorded at intake. Earlier external stages are not inferred.', 'document_id' => $data['source_document_id'], 'created_by' => $actor->id,
                    'procedure' => ['source' => 'CMO No.24 §34, PDF p.16', 'evidence' => Document::findOrFail($data['source_document_id'])->only(['id', 'public_id', 'sha256', 'document_type_id'])]]);
            }
            if ($type === 'monitoring') {
                Validator::make($data, ['items' => 'required|array|size:14', 'items.*.criterion_code' => 'required|distinct', 'items.*.compliance' => 'required|in:yes,no', 'items.*.verification' => 'required|string'])->validate();
                foreach ($data['items'] as $item) {
                    $criterion = config('monitoring.annex_m.'.$item['criterion_code'].'.criterion');
                    abort_unless($criterion, 422);
                    $r->items()->create(['criterion_code' => $item['criterion_code'], 'criterion' => $criterion, 'compliance' => $item['compliance'], 'verification' => $item['verification'], 'remarks' => $item['remarks'] ?? null]);
                }
            }
            Audit::record($actor, $type.'.recorded', $r);

            return $r;
        });
    }
}
