<?php

namespace App\Actions\Documents;

use App\Jobs\ScanDocument;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UploadDocument
{
    public function execute(User $actor, int $institutionId, int $typeId, UploadedFile $file, bool $signed, array $metadata = []): Document
    {
        app(Access::class)->authorize($actor, $institutionId, 'documents.upload');
        Validator::make(['file' => $file], ['file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:'.config('lakbay.upload_limit_kb')])->validate();
        $type = DocumentType::findOrFail($typeId);
        Validator::make(['metadata' => $metadata], ['metadata' => 'array:template_id,reference,semester,academic_year,coverage_from,coverage_until,prepared_by,reviewed_by,noted_by,signatory,position,notarial_reference',
            'metadata.template_id' => 'nullable|integer|exists:form_templates,id', 'metadata.coverage_from' => 'nullable|date', 'metadata.coverage_until' => 'nullable|date|after_or_equal:metadata.coverage_from',
            'metadata.reference' => 'nullable|string|max:255', 'metadata.semester' => 'nullable|string|max:255', 'metadata.academic_year' => 'nullable|string|max:255',
            'metadata.prepared_by' => 'nullable|string|max:255', 'metadata.reviewed_by' => 'nullable|string|max:255', 'metadata.noted_by' => 'nullable|string|max:255',
            'metadata.signatory' => 'nullable|string|max:255', 'metadata.position' => 'nullable|string|max:255', 'metadata.notarial_reference' => 'nullable|string|max:255'])->validate();
        if (! empty($metadata['template_id'])) {
            $template = FormTemplate::findOrFail($metadata['template_id']);
            abort_unless($template->code === $type->code, 422, 'Choose the template for this document type.');
            $metadata['template'] = $template->only(['id', 'code', 'version', 'annex', 'source_page', 'policy_version_id']);
        }
        if ($type->confidentiality === 'restricted') {
            app(Access::class)->authorize($actor, $institutionId, 'cases.sensitive', false, true);
        }
        if ($signed) {
            Validator::make(['file' => $file], ['file' => 'mimes:pdf'])->validate();
        }
        $hash = hash_file('sha256', $file->getRealPath());
        $path = $file->store('quarantine/'.Str::uuid(), 'private');
        try {
            $d = Document::create(['institution_id' => $institutionId, 'document_type_id' => $typeId, 'public_id' => (string) Str::uuid(), 'disk' => 'private', 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'sha256' => $hash, 'confidentiality' => $type->confidentiality, 'is_signed' => $signed, 'metadata' => $metadata, 'uploaded_by' => $actor->id]);
            Audit::record($actor, 'document.uploaded', $d);
            ScanDocument::dispatch($d->id);

            return $d->refresh();
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($path);
            throw $e;
        }
    }
}
