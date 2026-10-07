<?php

namespace App\Actions\Activities;

use App\Models\ActivityVersion;
use App\Models\Document;
use App\Models\User;
use App\Services\Access;
use App\Services\Reporting\ClocaBuilder;
use Illuminate\Support\Facades\Validator;

class PrepareConsentForm
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'student_name' => 'nullable|string|max:150',
            'program_year_level' => 'nullable|string|max:150',
            'student_number' => 'nullable|string|max:50',
            'student_contact' => 'nullable|string|max:50',
            'student_date' => 'nullable|date_format:Y-m-d',
            'guardian_name' => 'nullable|string|max:150',
            'relationship' => 'nullable|string|max:100',
            'emergency_contact' => 'nullable|string|max:50',
            'guardian_date' => 'nullable|date_format:Y-m-d',
            'student_privacy_date' => 'nullable|date_format:Y-m-d',
            'guardian_privacy_date' => 'nullable|date_format:Y-m-d',
            'letterhead_text' => 'nullable|string|max:1000',
            'letterhead_document_id' => 'nullable|integer',
        ];
    }

    public function authorize(User $actor, ActivityVersion $version): void
    {
        $institutionId = $version->activity->institution_id;
        abort_unless($actor->can('panel.hei') && $actor->can('activities.manage')
            && app(Access::class)->owns($actor, $institutionId), 403);
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function execute(User $actor, ActivityVersion $version, array $input = []): array
    {
        $this->authorize($actor, $version);
        $fields = Validator::make($input, $this->rules())->validate();
        $institution = $version->activity->institution;
        $image = ! empty($fields['letterhead_document_id']) ? Document::findOrFail($fields['letterhead_document_id']) : null;
        $data = [
            'renderer_version' => 1, 'activity_version_id' => $version->id, 'activity_revision' => $version->version,
            'template' => ['annex' => 'L', 'source' => 'CMO No. 24, s. 2026', 'source_page' => 32, 'version' => 1],
            'header' => ['institution_id' => $institution->id, 'hei_name' => $institution->name,
                'letterhead_text' => ($fields['letterhead_text'] ?? null) ?: $institution->name."\n".$institution->address,
                'letterhead' => $image ? ['id' => $image->id, 'sha256' => $image->sha256] : null],
            'basic' => ['title' => $version->title, 'organized_by' => $version->responsible_office,
                'dates' => $version->starts_at->timezone('Asia/Manila')->format('d M Y, H:i').' - '.$version->ends_at->timezone('Asia/Manila')->format('d M Y, H:i').' (Asia/Manila)',
                'venue' => $version->venue],
            'fields' => $fields,
        ];
        $data['letterhead_image'] = app(ClocaBuilder::class)->letterhead($actor, $data);

        return $data;
    }
}
