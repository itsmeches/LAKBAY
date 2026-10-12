<?php

namespace App\Actions\Operations;

use App\Models\CaseEvent;
use App\Models\CaseRecord;
use App\Models\Document;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\CaseProcedure;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RecordCaseEvent
{
    public function execute(User $actor, CaseRecord $case, string $status, string $notes, ?int $documentId = null, array $details = []): void
    {
        app(Access::class)->authorize($actor, $case->institution_id, 'cases.sensitive', false, true);
        abort_unless(array_key_exists($status, CaseProcedure::stages()) && trim($notes), 422);
        Validator::make($details, ['occurred_at' => 'nullable|date', 'event_key' => 'nullable|uuid', 'authority' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255', 'destination_region_id' => 'nullable|integer|exists:regions,id',
            'jurisdiction_basis' => 'nullable|in:hei,activity_location', 'activity_location' => 'nullable|string|max:1000',
            'historical_external' => 'nullable|boolean', 'history_explanation' => 'nullable|string|max:4000',
            'decision_event_id' => 'nullable|integer', 'dispatch_event_id' => 'nullable|integer',
            'recipient' => 'nullable|string|max:255', 'channel' => 'nullable|in:email,post,courier,personal,other'])->validate();
        $details = collect($details)->only(['authority', 'reference', 'destination_region_id', 'jurisdiction_basis', 'activity_location',
            'historical_external', 'history_explanation', 'decision_event_id', 'dispatch_event_id', 'recipient', 'channel', 'occurred_at', 'event_key'])->all();
        $occurred = empty($details['occurred_at']) ? CarbonImmutable::now() : CarbonImmutable::parse($details['occurred_at'], config('lakbay.timezone'))->utc();
        abort_if($occurred->isFuture(), 422, 'Case event time cannot be in the future.');
        $d = null;
        if ($documentId) {
            $d = Document::findOrFail($documentId);
            abort_unless($d->institution_id === $case->institution_id && $d->scan_status === 'clean' && $d->confidentiality === 'restricted' && app(Access::class)->document($actor, $d), 422, 'Use clean, restricted evidence for this institution.');
        }
        if (in_array($status, ['investigating', 'decided', 'closed'], true)) {
            abort_unless($d !== null && $d->is_signed && $d->mime === 'application/pdf' && $d->documentType->code === 'official_order', 422, 'A signed official decision or closure PDF is required.');
        }
        if (in_array($status, ['referred', 'fact_finding', 'investigation_findings', 'committee_recommendation', 'osds_referral', 'hei_dispatched', 'hei_received'], true)) {
            abort_unless($d !== null, 422, 'Record the supporting procedural evidence.');
        }
        abort_if($status === 'investigating' && $case->type !== 'investigation', 422, 'Record an authoritative investigation separately from the originating complaint.');
        DB::transaction(function () use ($actor, $case, $status, $notes, $documentId, $details, $occurred, $d) {
            $c = CaseRecord::whereKey($case->id)->lockForUpdate()->firstOrFail();
            $eventKey = $details['event_key'] ?? null;
            if ($eventKey && CaseEvent::where('event_key', $eventKey)->exists()) {
                abort(409, 'This event was already recorded. Refresh before continuing.');
            }
            abort_if(in_array($c->status, ['decided', 'closed'], true) && ! in_array($status, ['hei_dispatched', 'hei_received', 'closed'], true), 422, 'Do not reopen or replace decided history; record dispatch/receipt or signed closure.');
            $required = match ($status) {
                'referred' => ['destination_region_id', 'jurisdiction_basis', 'reference'],
                'decided', 'closed', 'investigating' => ['authority', 'reference'],
                'committee_recommendation', 'osds_referral' => ['reference'],
                'hei_dispatched' => ['decision_event_id', 'recipient', 'channel', 'reference'],
                'hei_received' => ['dispatch_event_id', 'recipient', 'reference'],
                default => [],
            };
            foreach ($required as $field) {
                if (! isset($details[$field]) || trim((string) $details[$field]) === '') {
                    throw ValidationException::withMessages(['details.'.$field => 'Record the '.str_replace('_', ' ', $field).' for this stage.']);
                }
            }
            if ($status === 'referred' && ($details['jurisdiction_basis'] ?? null) === 'activity_location' && empty($details['activity_location'])) {
                throw ValidationException::withMessages(['details.activity_location' => 'Record the activity location supporting this referral.']);
            }
            if ($status === 'referred' && ($details['jurisdiction_basis'] ?? null) === 'hei') {
                abort_unless((int) $details['destination_region_id'] === $c->institution->region_id, 422, 'HEI jurisdiction must match the recorded institution region; use documented activity-location jurisdiction where applicable.');
            }
            if ($status === 'decided') {
                abort_unless($details['authority'] === 'Commission en Banc (CEB)', 422, 'Record the CEB authority for a Section 34 decision.');
            }
            abort_if($occurred->lt($c->opened_at) && ! ($details['historical_external'] ?? false), 422, 'Earlier external events require historical mode and an explanation; retain their actual dates.');
            $prior = $c->events()->with('document')->where('occurred_at', '<=', $occurred)->get();
            $evidencedPrior = $prior->filter(fn ($event) => $event->document && $event->document->institution_id === $c->institution_id
                && $event->document->confidentiality === 'restricted' && $event->document->scan_status === 'clean' && app(Access::class)->document($actor, $event->document));
            $requiredPrior = match ($status) {
                'committee_recommendation' => ['fact_finding', 'investigation_findings'],
                'decided' => ['referred', 'fact_finding', 'investigation_findings', 'committee_recommendation'],
                'closed' => ['decided'],
                default => [],
            };
            $missing = array_values(array_diff($requiredPrior, $evidencedPrior->pluck('type')->all()));
            if ($missing && ! ($details['historical_external'] ?? false)) {
                throw ValidationException::withMessages(['details.history_explanation' => 'Record prior stages first, or explicitly document historical external evidence and unknown history: '.implode(', ', $missing).'.']);
            }
            if (($details['historical_external'] ?? false) && ! trim($details['history_explanation'] ?? '')) {
                throw ValidationException::withMessages(['details.history_explanation' => 'Explain historical external evidence and the stages not available in this registry.']);
            }
            foreach (['hei_dispatched' => ['decision_event_id', 'decided'], 'hei_received' => ['dispatch_event_id', 'hei_dispatched']] as $type => [$key, $expected]) {
                if ($status === $type) {
                    abort_unless($evidencedPrior->contains(fn ($event) => $event->id === (int) $details[$key] && $event->type === $expected), 422, 'Link the earlier evidenced event from this same case.');
                }
            }
            $procedure = collect($details)->except(['occurred_at', 'event_key'])->all() + ['source' => 'CMO No.24 §34, PDF p.16', 'missing_prior_stages' => $missing,
                'evidence' => $d?->only(['id', 'public_id', 'sha256', 'document_type_id'])];
            $event = CaseEvent::create(['case_record_id' => $c->id, 'type' => $status, 'occurred_at' => $occurred, 'notes' => trim($notes), 'document_id' => $documentId, 'created_by' => $actor->id, 'procedure' => $procedure, 'event_key' => $eventKey]);
            if (in_array($status, ['investigating', 'decided', 'closed'], true) || (in_array($status, ['received', 'referred'], true) && in_array($c->status, ['received', 'referred'], true))) {
                $c->update(['status' => $status, 'closed_at' => $status === 'closed' ? $occurred : null]);
            }
            Audit::record($actor, 'case.status_recorded', $c, ['status' => $status, 'case_event_id' => $event->id, 'missing_prior_stages' => $missing]);
        });
    }
}
