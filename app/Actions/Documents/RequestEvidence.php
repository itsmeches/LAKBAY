<?php

namespace App\Actions\Documents;

use App\Jobs\NotifyInstitution;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Submission;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use App\Services\Compliance\PolicyRules;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RequestEvidence
{
    public function execute(User $actor, Submission $submission, array $data): DocumentRequest
    {
        app(Access::class)->authorize($actor, $submission->institution_id, 'submissions.review', false, true);
        $retainedRule = $submission->policyVersion->requirements()->where('stage', 'requested_evidence')
            ->whereHas('requirementDefinition', fn ($q) => $q->where('document_type_id', $data['document_type_id'] ?? 0))->first();
        $immediate = PolicyRules::isCmo($submission->policyVersion) && $retainedRule;
        Validator::make($data, ['document_type_id' => 'required|exists:document_types,id', 'reason' => 'required|string', 'due_at' => $immediate ? 'nullable|date' : 'required|date'])->validate();
        abort_if(DocumentType::findOrFail($data['document_type_id'])->confidentiality === 'restricted', 422, 'Restricted case evidence uses the restricted case workflow.');
        $r = DocumentRequest::create(['institution_id' => $submission->institution_id, 'submission_id' => $submission->id, 'requested_by' => $actor->id, 'document_type_id' => $data['document_type_id'], 'reason' => $data['reason'],
            'due_at' => $immediate ? now() : CarbonImmutable::parse($data['due_at'], config('lakbay.timezone'))->utc(),
            'signature_mode' => $retainedRule?->signature_mode ?? 'copy', 'deadline_parameters' => ['type' => $immediate ? 'immediate_on_request' : 'specified', 'source_reference' => $retainedRule?->source_reference]]);
        Audit::record($actor, 'evidence.requested', $r);
        NotifyInstitution::dispatch($r->institution_id, 'evidence-request-'.$r->id, 'Supporting evidence requested', 'Sign in to review CHED’s document request.');

        return $r;
    }

    public function respond(User $actor, DocumentRequest $request, int $documentId): DocumentRequest
    {
        app(Access::class)->authorize($actor, $request->institution_id, 'submissions.submit', true);

        return DB::transaction(function () use ($actor, $request, $documentId) {
            $r = DocumentRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            abort_unless($r->status === 'requested', 422);
            $d = Document::findOrFail($documentId);
            abort_unless($d->institution_id === $r->institution_id && $d->document_type_id === $r->document_type_id && $d->scan_status === 'clean', 422);
            abort_unless(app(Access::class)->document($actor, $d) && ($r->signature_mode !== 'signed' || ($d->is_signed && $d->mime === 'application/pdf')), 422);
            $r->update(['status' => 'submitted', 'response_document_id' => $d->id, 'responded_at' => now()]);
            Audit::record($actor, 'evidence.response_received', $r);

            return $r;
        });
    }
}
