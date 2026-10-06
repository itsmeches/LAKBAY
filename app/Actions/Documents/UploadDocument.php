<?php

namespace App\Actions\Documents;

use App\Jobs\ScanDocument;
use App\Models\Document;
use App\Models\DocumentType;
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
