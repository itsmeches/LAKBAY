<?php

namespace App\Filament\Support;

use App\Actions\Activities\SaveActivity;
use App\Actions\Documents\UploadDocument;
use App\Actions\Identity\SaveReference;
use App\Actions\Operations\RecordOperationalRecord;
use App\Actions\Reports\SubmitReport;
use App\Models\DocumentType;
use App\Models\IncidentReport;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class Definitions
{
    public const MAP = [
        'Institution' => [
            'perm' => 'institutions.manage',
            'fields' => [
                'ched_code' => 'text',
                'name' => 'text',
                'institution_type' => [
                    'suc' => 'SUC',
                    'luc' => 'LUC',
                    'private' => 'Private',
                ],
                'address' => 'optional_area',
                'email' => 'optional',
            ],
            'columns' => [
                0 => 'ched_code',
                1 => 'name',
                2 => 'institution_type',
            ],
            'action' => 'reference',
        ],
        'Campus' => [
            'perm' => 'institutions.manage',
            'fields' => [
                'name' => 'text',
                'address' => 'optional_area',
                'locality' => 'optional',
            ],
            'columns' => [
                0 => 'name',
                1 => 'locality',
            ],
            'action' => 'reference',
        ],
        'AcademicPeriod' => [
            'perm' => 'activities.manage',
            'fields' => [
                'label' => 'text',
                'starts_on' => 'date',
                'ends_on' => 'date',
                'term' => 'optional',
            ],
            'columns' => [
                0 => 'label',
                1 => 'starts_on',
                2 => 'ends_on',
            ],
            'action' => 'reference',
        ],
        'Calendar' => [
            'perm' => 'activities.manage',
            'fields' => [
                'title' => 'text',
                'effective_on' => 'optional_date',
                'responsible_office' => 'text',
            ],
            'relations' => [
                'academic_period_id' => [
                    0 => 'AcademicPeriod',
                    1 => 'label',
                ],
            ],
            'columns' => [
                0 => 'title',
                1 => 'responsible_office',
                2 => 'effective_on',
            ],
            'action' => 'reference',
        ],
        'Activity' => [
            'perm' => 'activities.manage',
            'fields' => [
                'title' => 'text',
                'category' => [
                    1 => 'Category 1',
                    2 => 'Category 2',
                    3 => 'Category 3',
                ],
                'academic_nature' => [
                    'curricular' => 'Curricular',
                    'non_curricular' => 'Non-curricular',
                ],
                'activity_type' => 'text',
                'initiator' => [
                    'hei' => 'HEI',
                    'student' => 'Student',
                    'external' => 'External',
                ],
                'program' => 'optional',
                'description' => 'area',
                'learning_objectives' => 'area',
                'responsible_office' => 'text',
                'starts_at' => 'datetime',
                'ends_at' => 'datetime',
                'venue' => 'text',
                'student_count' => 'number',
                'other_count' => 'number',
                'authorization.official' => 'text',
                'authorization.date' => 'date',
                'classification_reason' => 'optional',
            ],
            'relations' => [
                'academic_period_id' => [
                    0 => 'AcademicPeriod',
                    1 => 'label',
                ],
                'campus_id' => [
                    0 => 'Campus',
                    1 => 'name',
                    2 => false,
                ],
                'calendar_id' => [
                    0 => 'Calendar',
                    1 => 'title',
                    2 => false,
                ],
            ],
            'columns' => [
                0 => 'title',
                1 => 'execution_status',
                2 => 'latestVersion.category',
                3 => 'latestVersion.starts_at',
            ],
            'action' => 'activity',
        ],
        'Document' => [
            'perm' => 'documents.upload',
            'fields' => [
                'is_signed' => 'boolean',
            ],
            'columns' => [
                0 => 'original_name',
                1 => 'documentType.name',
                2 => 'scan_status',
                3 => 'is_signed',
            ],
            'action' => 'document',
        ],
        'Submission' => [
            'perm' => null,
            'fields' => [
            ],
            'columns' => [
                0 => 'reference',
                1 => 'type',
                2 => 'status',
                3 => 'submitted_at',
                4 => 'is_late',
            ],
            'detail' => [
                0 => 'reference',
                1 => 'status',
                2 => 'submitted_at',
                3 => 'is_late',
                4 => 'snapshot',
            ],
            'action' => null,
        ],
        'CompletionReport' => [
            'perm' => 'reports.submit',
            'fields' => [
                'ended_at' => 'datetime',
                'venue' => 'text',
                'student_count' => 'number',
                'implementation' => [
                    'as_authorized' => 'As authorized by HEI',
                    'with_changes' => 'With changes',
                ],
                'summary' => 'area',
                'incidents_encountered' => 'boolean',
            ],
            'relations' => [
                'activity_id' => [
                    0 => 'Activity',
                    1 => 'title',
                ],
                'document_id' => [
                    0 => 'Document',
                    1 => 'original_name',
                ],
            ],
            'columns' => [
                0 => 'activity.title',
                1 => 'ended_at',
                2 => 'is_late',
                3 => 'implementation',
            ],
            'action' => 'completion',
        ],
        'IncidentReport' => [
            'perm' => 'reports.submit',
            'read' => 'incidents.view',
            'fields' => [
                'occurred_at' => 'datetime',
                'location' => 'text',
                'type' => 'text',
                'description' => 'area',
                'immediate_actions' => 'area',
                'current_status' => 'area',
            ],
            'relations' => [
                'activity_id' => [
                    0 => 'Activity',
                    1 => 'title',
                ],
                'document_id' => [
                    0 => 'Document',
                    1 => 'original_name',
                    2 => false,
                ],
            ],
            'columns' => [
                0 => 'activity.title',
                1 => 'occurred_at',
                2 => 'type',
                3 => 'is_late',
            ],
            'action' => 'incident',
        ],
        'ChangeReport' => [
            'perm' => 'reports.submit',
            'fields' => [
                'changed_at' => 'datetime',
                'reason' => 'area',
            ],
            'relations' => [
                'activity_id' => [
                    0 => 'Activity',
                    1 => 'title',
                ],
                'original_version_id' => [
                    0 => 'ActivityVersion',
                    1 => 'title',
                ],
                'revised_version_id' => [
                    0 => 'ActivityVersion',
                    1 => 'title',
                ],
                'document_id' => [
                    0 => 'Document',
                    1 => 'original_name',
                ],
            ],
            'columns' => [
                0 => 'activity.title',
                1 => 'changed_at',
                2 => 'is_late',
            ],
            'action' => 'change',
        ],
        'MonitoringRecord' => [
            'perm' => 'monitoring.manage',
            'fields' => [
                'period' => 'text',
                'monitored_on' => 'date',
                'type' => [
                    'desk' => 'Desk',
                    'virtual' => 'Virtual',
                    'on_site' => 'On-site',
                    'incident_based' => 'Incident-based',
                ],
                'respondent' => 'text',
                'overall_rating' => [
                    'compliant' => 'Compliant',
                    'non_compliant' => 'Non-compliant',
                ],
                'findings' => 'area',
                'good_practices' => 'optional_area',
                'suggestions' => 'optional_area',
            ],
            'relations' => [
                'activity_id' => [
                    0 => 'Activity',
                    1 => 'title',
                    2 => false,
                ],
            ],
            'columns' => [
                0 => 'institution.name',
                1 => 'monitored_on',
                2 => 'type',
                3 => 'overall_rating',
            ],
            'action' => 'monitoring',
        ],
        'TechnicalAssistanceLog' => [
            'perm' => 'monitoring.manage',
            'fields' => [
                'type' => 'text',
                'provided_on' => 'date',
                'summary' => 'area',
                'follow_up' => 'optional_area',
            ],
            'relations' => [
                'activity_id' => [
                    0 => 'Activity',
                    1 => 'title',
                    2 => false,
                ],
            ],
            'columns' => [
                0 => 'institution.name',
                1 => 'provided_on',
                2 => 'type',
                3 => 'summary',
            ],
            'action' => 'assistance',
        ],
        'CaseRecord' => [
            'perm' => 'cases.sensitive',
            'read' => 'cases.sensitive',
            'fields' => [
                'reference' => 'text',
                'type' => [
                    'complaint' => 'Complaint',
                    'investigation' => 'Investigation',
                ],
                'summary' => 'area',
                'status' => [
                    'received' => 'Received',
                    'referred' => 'Referred',
                    'investigating' => 'Investigating',
                    'decided' => 'Decided',
                    'closed' => 'Closed',
                ],
                'opened_at' => 'datetime',
            ],
            'relations' => [
                'activity_id' => [
                    0 => 'Activity',
                    1 => 'title',
                    2 => false,
                ],
            ],
            'columns' => [
                0 => 'reference',
                1 => 'institution.name',
                2 => 'type',
                3 => 'status',
            ],
            'action' => 'case',
        ],
        'ClearanceRequest' => [
            'perm' => 'cases.sensitive',
            'read' => 'cases.sensitive',
            'fields' => [
                'justification' => 'area',
                'endorsement_reference' => 'optional',
                'osds_reference' => 'optional',
            ],
            'relations' => [
                'activity_version_id' => [
                    0 => 'ActivityVersion',
                    1 => 'title',
                ],
                'case_record_id' => [
                    0 => 'CaseRecord',
                    1 => 'reference',
                ],
            ],
            'columns' => [
                0 => 'institution.name',
                1 => 'activityVersion.title',
                2 => 'status',
                3 => 'decision',
            ],
            'action' => 'clearance',
        ],
        'InstitutionalRestriction' => [
            'perm' => 'cases.sensitive',
            'read' => 'cases.sensitive',
            'fields' => [
                'type' => [
                    'suspension' => 'Suspension',
                    'clearance_required' => 'Clearance required',
                    'corrective_order' => 'Corrective order',
                ],
                'effective_from' => 'date',
                'effective_until' => 'optional_date',
                'issuing_authority' => 'text',
            ],
            'relations' => [
                'activity_id' => [
                    0 => 'Activity',
                    1 => 'title',
                    2 => false,
                ],
                'document_id' => [
                    0 => 'Document',
                    1 => 'original_name',
                ],
            ],
            'columns' => [
                0 => 'institution.name',
                1 => 'type',
                2 => 'effective_from',
                3 => 'effective_until',
            ],
            'action' => 'restriction',
        ],
    ];

    public static function form(string $model): array
    {
        $name = class_basename($model);
        $d = self::MAP[$name];
        $fields = [];
        if ($name !== 'Institution') {
            $fields[] = Fields::institution();
        }
        foreach ($d['relations'] ?? [] as $key => $value) {
            $fields[] = Fields::relation($key, 'App\\Models\\'.$value[0], $value[1], $value[2] ?? true);
        }
        foreach ($d['fields'] as $key => $type) {
            $fields[] = Fields::make($key, $type);
        }
        if ($name === 'Activity') {
            foreach (['has_student_costs', 'requires_accommodation', 'external_invitation', 'strenuous', 'vulnerable_participants', 'hazardous_destination', 'long_distance', 'class_size'] as $key) {
                $fields[] = Toggle::make('conditions.'.$key)->default(false);
            }
            $fields[] = Repeater::make('personnel')->schema([Select::make('role')->options(['pic' => 'PIC', 'apic' => 'APIC', 'supervisor' => 'Supervisor'])->required(), TextInput::make('name')->required(), TextInput::make('position')->required(), TextInput::make('contact')->required(), TextInput::make('qualifications'), Toggle::make('first_aid_trained')])->minItems(1)->defaultItems(1)->columnSpanFull();
        }
        if ($name === 'Document') {
            $fields[] = Select::make('document_type_id')->options(fn () => DocumentType::query()->when(! auth()->user()->can('cases.sensitive'), fn ($q) => $q->where('confidentiality', '!=', 'restricted'))->pluck('name', 'id'))->required();
            $fields[] = FileUpload::make('file')->storeFiles(false)->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(config('lakbay.upload_limit_kb'))->required()->helperText('PDF, JPG or PNG, up to 20 MB. Signed official documents must be PDFs. Files remain private pending malware scanning.');
        }
        if ($name === 'MonitoringRecord') {
            $fields[] = Repeater::make('items')->schema([TextInput::make('criterion_code')->readOnly()->required(), TextInput::make('criterion')->readOnly()->required(), Select::make('compliance')->options(['yes' => 'Yes', 'no' => 'No'])->required(), TextInput::make('verification')->required(), TextInput::make('remarks')])->default(collect(config('monitoring.criteria'))->map(fn ($text, $code) => ['criterion_code' => (string) $code, 'criterion' => $text])->values()->all())->addable(false)->deletable(false)->reorderable(false)->columnSpanFull();
        }
        if ($name === 'IncidentReport') {
            $fields[] = Fields::relation('supplements_report_id', IncidentReport::class, 'type', false);
            $fields[] = Repeater::make('persons')->schema([TextInput::make('name')->required(), TextInput::make('role')->required(), TextInput::make('remarks')])->defaultItems(0);
        }

        if ($name === 'Activity') {
            $groups = [
                '1. Institution and calendar' => [],
                '2. Activity purpose and classification' => [],
                '3. Schedule and participants' => [],
                '4. Risk conditions and personnel' => [],
                '5. HEI authorization' => [],
            ];
            foreach ($fields as $field) {
                $key = $field->getName();
                $group = match (true) {
                    in_array($key, ['institution_id', 'academic_period_id', 'campus_id', 'calendar_id'], true) => '1. Institution and calendar',
                    in_array($key, ['starts_at', 'ends_at', 'venue', 'student_count', 'other_count'], true) => '3. Schedule and participants',
                    str_starts_with($key, 'conditions.') || $key === 'personnel' => '4. Risk conditions and personnel',
                    str_starts_with($key, 'authorization.') => '5. HEI authorization',
                    default => '2. Activity purpose and classification',
                };
                $groups[$group][] = $field;
            }

            return array_map(
                fn (string $title, array $components) => Section::make($title)->schema($components)->columns(2)->columnSpanFull(),
                array_keys($groups),
                array_values($groups),
            );
        }

        return [Section::make('Record details')->schema($fields)->columns(2)->columnSpanFull()];
    }

    public static function columns(string $model): array
    {
        return self::MAP[class_basename($model)]['columns'];
    }

    public static function detailFields(string $model): array
    {
        $d = self::MAP[class_basename($model)];

        return $d['detail'] ?? array_values(array_unique(array_merge($d['columns'], array_keys($d['fields']), ['created_at'])));
    }

    public static function create(string $model, array $data)
    {
        foreach (['institution_id', 'academic_period_id', 'calendar_id', 'campus_id', 'activity_id', 'original_version_id', 'revised_version_id', 'document_id', 'activity_version_id', 'case_record_id'] as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $data[$key] = (int) $data[$key];
            }
        }
        $actor = auth()->user();
        $type = self::MAP[class_basename($model)]['action'];

        return match ($type) {
            'reference' => app(SaveReference::class)->execute($actor, $model, $data),
            'activity' => app(SaveActivity::class)->execute($actor, $data),
            'document' => app(UploadDocument::class)->execute($actor, (int) $data['institution_id'], (int) $data['document_type_id'], $data['file'], (bool) $data['is_signed']),
            'completion','incident','change' => app(SubmitReport::class)->execute($actor, $type, $data),
            'monitoring','assistance','case','clearance','restriction' => app(RecordOperationalRecord::class)->execute($actor, $type, $data),
            default => throw new \LogicException('Creation is not available')
        };
    }
}
