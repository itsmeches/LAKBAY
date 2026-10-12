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
use Illuminate\Validation\ValidationException;

class RequestEvidence
{
    /** @return array<int, string> */
    public function responseOptions(User $actor, DocumentRequest $request): array
    {
        return Document::query()->where('institution_id', $request->institution_id)
            ->where('document_type_id', $request->document_type_id)->where('scan_status', 'clean')
            ->when($request->signature_mode === 'signed', fn ($query) => $query->where('is_signed', true)->where('mime', 'application/pdf'))
            ->get()->filter(fn (Document $document) => app(Access::class)->document($actor, $document))
            ->pluck('original_name', 'id')->all();
    }

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
            if ($r->status !== 'requested') {
                throw ValidationException::withMessages(['document_id' => 'This request has already been answered. Refresh the evidence requests page.']);
            }
            $d = Document::findOrFail($documentId);
            abort_unless($d->institution_id === $r->institution_id && app(Access::class)->document($actor, $d), 403);
            if ($d->document_type_id !== $r->document_type_id) {
                throw ValidationException::withMessages(['document_id' => 'Choose a document with the requested document type: '.$r->documentType->name.'.']);
            }
            if ($d->scan_status !== 'clean') {
                throw ValidationException::withMessages(['document_id' => 'Wait for this document to pass malware scanning before responding.']);
            }
            if ($r->signature_mode === 'signed' && (! $d->is_signed || $d->mime !== 'application/pdf')) {
                throw ValidationException::withMessages(['document_id' => 'This request requires a signed PDF of the requested document type.']);
            }
            $r->update(['status' => 'submitted', 'response_document_id' => $d->id, 'responded_at' => now()]);
            Audit::record($actor, 'evidence.response_received', $r);

            return $r;
        });
    }
}
