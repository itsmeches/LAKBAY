<?php

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Access;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;

class RegisterPublicTemplate
{
    public function execute(User $actor, FormTemplate $template, int $documentId): void
    {
        abort_unless($actor->is_active && $actor->hasVerifiedEmail() && $actor->can('policy.manage') && $actor->can('panel.ched'), 403);
        $d = Document::findOrFail($documentId);
        abort_unless(app(Access::class)->regional($actor, $d->institution_id), 403);
        abort_unless($d->confidentiality === 'institutional' && ! $d->is_signed && $d->scan_status === 'clean' && $d->mime === 'application/pdf' && $d->documentType->code === $template->code, 422, 'Choose a clean unsigned blank PDF of the matching form type.');
        DB::transaction(function () use ($actor, $template, $d) {
            $t = FormTemplate::whereKey($template->id)->lockForUpdate()->firstOrFail();
            $t->update(['document_id' => $d->id, 'is_public' => true]);
            Audit::record($actor, 'template.publication_registered', $t, ['document_id' => $d->id]);
        });
    }
}
