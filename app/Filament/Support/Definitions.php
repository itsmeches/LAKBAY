<?php

namespace App\Filament\Support;

use App\Actions\Activities\SaveActivity;
use App\Actions\Activities\SaveCertificationDraft;
use App\Actions\Activities\SaveSaaDraft;
use App\Actions\Documents\UploadDocument;
use App\Actions\Identity\SaveReference;
use App\Actions\Operations\RecordOperationalRecord;
use App\Actions\Reports\SaveChangeReportDraft;
use App\Actions\Reports\SaveCompletionReportDraft;
use App\Actions\Reports\SaveIncidentReportDraft;
use App\Actions\Reports\SubmitReport;
use App\Models\AcademicPeriod;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\FormTemplate;
use App\Models\IncidentReport;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class Definitions
{
    public const MAP = [
        'ChangeReportDraft' => [
            'perm' => 'reports.submit', 'fields' => [],
            'columns' => ['title', 'activity.title', 'revision'],
            'detail' => ['title', 'original_version_id', 'revised_version_id', 'original_submission_id', 'supplements_report_id', 'revision', 'details', 'created_at'],
            'action' => 'change_draft',
        ],
        'IncidentReportDraft' => [
            'perm' => 'reports.submit', 'fields' => [],
            'columns' => ['title', 'activity.title', 'revision'],
            'detail' => ['title', 'activity_version_id', 'supplements_report_id', 'revision', 'details', 'created_at'],
            'action' => 'incident_draft',
        ],
        'CompletionReportDraft' => [
            'perm' => 'reports.submit', 'fields' => [],
            'columns' => ['title', 'activity.title', 'revision'],
            'detail' => ['title', 'activityVersion.title', 'activity_version_id', 'revision', 'details', 'created_at'],
            'action' => 'roc_draft',
        ],
        'CertificationDraft' => [
            'perm' => 'activities.manage', 'fields' => [],
            'columns' => ['title', 'academicPeriod.label', 'revision'],
            'detail' => ['title', 'academicPeriod.label', 'revision', 'details', 'created_at'],
            'action' => 'certification',
        ],
        'AccountabilityAffidavit' => [
            'perm' => 'activities.manage', 'fields' => [],
            'columns' => ['title', 'academicPeriod.label', 'revision'],
            'detail' => ['title', 'academicPeriod.label', 'revision', 'details', 'created_at'],
            'action' => 'saa',
        ],
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
                'change_explanation' => 'optional_area',
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
                4 => 'intake_kind',
            ],
            'detail' => ['activity.title', 'occurred_at', 'location', 'type', 'description', 'immediate_actions', 'current_status', 'intake_kind', 'supplements_report_id', 'due_at', 'is_late', 'deadline_parameters', 'created_at'],
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
                    2 => false,
                ],
            ],
            'columns' => [
                0 => 'activity.title',
                1 => 'changed_at',
                2 => 'is_late',
                3 => 'intake_kind',
            ],
            'detail' => ['activity.title', 'changed_at', 'reason', 'intake_kind', 'notified_at', 'original_submission_reference', 'due_at', 'is_late', 'deadline_parameters', 'created_at'],
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
                'annex_metadata.respondent_position' => 'optional',
                'annex_metadata.prepared_by' => 'optional',
                'annex_metadata.prepared_position' => 'optional',
                'annex_metadata.reviewed_by' => 'optional',
                'annex_metadata.reviewed_position' => 'optional',
                'annex_metadata.noted_by' => 'optional',
                'annex_metadata.noted_position' => 'optional',
                'annex_metadata.hei_conforme' => 'optional',
                'annex_metadata.hei_conforme_date' => 'optional_date',
                'annex_metadata.other_findings' => 'optional_area',
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
                'source_document_id' => ['Document', 'original_name'],
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
                'application_received_at' => 'datetime',
            ],
            'relations' => [
                'application_document_id' => ['Document', 'original_name'],
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
        if ($name === 'IncidentReportDraft') {
            return [...IncidentReportForms::source(), ...IncidentReportForms::details()];
        }
        if ($name === 'ChangeReportDraft') {
            return [...ChangeReportForms::source(), ...ChangeReportForms::details()];
        }
        if ($name === 'CompletionReportDraft') {
            return [...CompletionReportForms::source(), ...CompletionReportForms::details()];
        }
        if ($name === 'CertificationDraft') {
            return [Section::make('Institution and academic period')->description('The academic period organizes draft selection; it does not establish official certification coverage.')
                ->schema([Fields::institution(), Fields::relation('academic_period_id', AcademicPeriod::class, 'label')])->columns(2)->columnSpanFull(), ...CertificationForms::details()];
        }
        if ($name === 'AccountabilityAffidavit') {
            return [Section::make('Institution and academic period')->schema([Fields::institution(), Fields::relation('academic_period_id', AcademicPeriod::class, 'label')])->columns(2)->columnSpanFull(), ...SaaForms::details()];
        }
        if ($name === 'Calendar') {
            return [
                Section::make('1. Institution and coverage')->schema([Fields::institution(), Fields::relation('academic_period_id', AcademicPeriod::class, 'label')])->columns(2)->columnSpanFull(),
                Section::make('2. CLOCA header and signatories (Annex B)')->schema(ClocaForms::calendarDetails())->columns(2)->columnSpanFull(),
            ];
        }
        $d = self::MAP[$name];
        $fields = [];
        if ($name !== 'Institution') {
            $fields[] = Fields::institution();
        }
        foreach ($d['relations'] ?? [] as $key => $value) {
            $fields[] = Fields::relation($key, 'App\\Models\\'.$value[0], $value[1], $value[2] ?? true);
        }
        foreach ($d['fields'] as $key => $type) {
            $field = Fields::make($key, $type);
            if ($name === 'MonitoringRecord') {
                $labels = ['period' => 'Period covered', 'monitored_on' => 'Date of monitoring', 'type' => 'Type of monitoring',
                    'respondent' => 'HEI respondent', 'findings' => 'Deficiencies noted', 'good_practices' => 'Good practices',
                    'suggestions' => 'Suggested measures for continuous improvement', 'overall_rating' => 'Operational overall rating',
                    'annex_metadata.respondent_position' => 'Respondent position/designation',
                    'annex_metadata.prepared_by' => 'Prepared by — printed name', 'annex_metadata.prepared_position' => 'Preparer designation/position',
                    'annex_metadata.reviewed_by' => 'Reviewed by — printed name', 'annex_metadata.reviewed_position' => 'Reviewer designation/position',
                    'annex_metadata.noted_by' => 'Noted by — printed name', 'annex_metadata.noted_position' => 'Noting official designation/position',
                    'annex_metadata.hei_conforme' => 'HEI conforme — printed name', 'annex_metadata.hei_conforme_date' => 'HEI conforme date (optional)',
                    'annex_metadata.other_findings' => 'Others (please specify)'];
                $field->label($labels[$key] ?? $key);
                if (in_array($key, ['findings', 'good_practices', 'suggestions', 'annex_metadata.other_findings'])) {
                    $field->maxLength(2000)->helperText('Use institutional findings; avoid individual medical details and restricted-case narratives.');
                }
                if ($key === 'overall_rating') {
                    $field->helperText('Human-entered operational rating; not an Annex M score, sanction or authorization.');
                }
            }
            $fields[] = $field;
        }
        if ($name === 'Activity') {
            foreach (['has_student_costs', 'requires_accommodation', 'external_invitation', 'strenuous', 'vulnerable_participants', 'hazardous_destination', 'long_distance', 'class_size', 'requires_physical_activity', 'requires_travel', 'venue_requires_accreditation', 'medical_clearance_relevant', 'has_third_party_services'] as $key) {
                $fields[] = Toggle::make('conditions.'.$key)->default(false);
            }
            $fields[] = Repeater::make('personnel')->schema([
                Select::make('role')->options(['pic' => 'PIC', 'apic' => 'APIC', 'supervisor' => 'Supervisor'])->required()->live(),
                TextInput::make('name')->required(), TextInput::make('position')->required(), TextInput::make('contact')->required(),
                TextInput::make('qualifications')->label('Relevant qualifications or experience'),
                Toggle::make('first_aid_trained')->label('First-aid training completed'),
                Toggle::make('basic_first_aid_knowledge')->label('APIC basic first-aid knowledge')->visible(fn (Get $get) => $get('role') === 'apic')
                    ->helperText('Confirm basic knowledge; a separate APIC training-proof upload is not required. Sec. 12.H, PDF pp.5–6; Annex F, PDF p.25.'),
                Toggle::make('hei_employed')->label('Employed by the HEI'),
                Toggle::make('accompanies_activity')->label('Accompanies participating students'),
                TextInput::make('first_aid_reference')->label('PIC first-aid training evidence reference')->visible(fn (Get $get) => $get('role') === 'pic'),
            ])->helperText('Categories 1/2 (including escalated Category 3): qualify every designated PIC/APIC. APICs do not count toward the PIC ratio. Ordinary Category 3 may use a supervising faculty member.')->minItems(1)->defaultItems(1)->columnSpanFull();
            $fields[] = Repeater::make('contracts')->schema([Fields::relation('document_id', Document::class, 'original_name'), TextInput::make('provider')->required(), TextInput::make('service')->required(), Fields::make('effective_from', 'date'), Fields::make('effective_until', 'optional_date')])->defaultItems(0)->columnSpanFull();
            foreach (['inclusivity', 'parallel_learning', 'grievance_mechanism', 'student_participation', 'emergency_preparedness', 'cost_consultation'] as $key) {
                $fields[] = Fields::make('preparation.'.$key, 'optional_area');
            }
        }
        if ($name === 'MonitoringRecord') {
            $information = ['institution_id', 'activity_id', 'period', 'monitored_on', 'type', 'respondent', 'annex_metadata.respondent_position'];
            $findings = ['overall_rating', 'findings', 'good_practices', 'suggestions', 'annex_metadata.other_findings'];
            $fields[] = Repeater::make('items')->label('Annex M compliance matrix (14 items)')
                ->schema([TextInput::make('criterion_code')->readOnly()->required(), Textarea::make('criterion')->readOnly()->required(),
                    Select::make('compliance')->options(['yes' => 'Yes', 'no' => 'No'])->required(),
                    Textarea::make('verification')->label('Means of verification')->required()->maxLength(2000)->helperText('Template examples are prefilled. Confirm/edit the evidence actually reviewed; avoid personal medical details.'),
                    Textarea::make('remarks')->maxLength(2000)])
                ->default(collect(config('monitoring.annex_m'))->map(fn ($row, $code) => ['criterion_code' => (string) $code, 'criterion' => $row['criterion'], 'verification' => $row['verification']])->values()->all())
                ->addable(false)->deletable(false)->reorderable(false)->columnSpanFull();

            return [
                Section::make('I–II. Monitoring information and HEI profile')->schema(array_values(array_filter($fields, fn ($field) => in_array($field->getName(), $information))))->columns(2)->columnSpanFull(),
                Section::make('Compliance matrix')->schema(array_values(array_filter($fields, fn ($field) => $field->getName() === 'items')))->columnSpanFull(),
                Section::make('III. Findings and observations')->schema(array_values(array_filter($fields, fn ($field) => in_array($field->getName(), $findings))))->columns(2)->columnSpanFull(),
                Section::make('Printed signatories and HEI conforme')->description('Names and dates are preparation only. Sign externally; no electronic signature or acceptance is inferred. Region, personnel and HEI type are captured from authorized records.')->schema(array_values(array_filter($fields, fn ($field) => ! in_array($field->getName(), array_merge($information, $findings, ['items'])))))->columns(2)->columnSpanFull(),
            ];
        }
        if ($name === 'Document') {
            $fields[] = Select::make('document_type_id')->options(fn () => DocumentType::query()->when(! auth()->user()->can('cases.sensitive'), fn ($q) => $q->where('confidentiality', '!=', 'restricted'))->pluck('name', 'id'))->required();
            $fields[] = FileUpload::make('file')->storeFiles(false)->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(config('lakbay.upload_limit_kb'))->required()->helperText('PDF, JPG or PNG, up to 20 MB. Signed official documents must be PDFs. Files remain private pending malware scanning.');
            $fields[] = Select::make('metadata.template_id')->label('Form template version (optional)')->options(fn () => FormTemplate::with('policyVersion')->get()->mapWithKeys(fn ($t) => [$t->id => $t->policyVersion->code.' / Annex '.$t->annex.' / '.$t->title]));
            foreach (['reference', 'semester', 'academic_year', 'prepared_by', 'reviewed_by', 'noted_by', 'signatory', 'position', 'notarial_reference'] as $key) {
                $fields[] = Fields::make('metadata.'.$key, 'optional');
            }
            $fields[] = Fields::make('metadata.coverage_from', 'optional_date');
            $fields[] = Fields::make('metadata.coverage_until', 'optional_date');
        }
        if ($name === 'IncidentReport') {
            $fields[] = Fields::relation('supplements_report_id', IncidentReport::class, 'type', false);
            $fields[] = Repeater::make('persons')->schema([TextInput::make('name')->required(), TextInput::make('role')->required(), TextInput::make('remarks')])->defaultItems(0);
        }
        if ($name === 'CompletionReport') {
            $fields[] = Select::make('incident_report_ids')->multiple()->options(fn () => Fields::related(IncidentReport::class, 'type'))->helperText('Link applicable incident reports or initial notices; signed originals remain private.');
        }

        if ($name === 'Activity') {
            $groups = [
                '1. Institution and calendar' => [],
                '2. Activity purpose and classification' => [],
                '3. Schedule and participants' => [],
                '4. Risk conditions and personnel' => [],
                '5. Preparation evidence and contracts' => [],
                '6. HEI authorization' => [],
            ];
            foreach ($fields as $field) {
                $key = $field->getName();
                $group = match (true) {
                    in_array($key, ['institution_id', 'academic_period_id', 'campus_id', 'calendar_id'], true) => '1. Institution and calendar',
                    in_array($key, ['starts_at', 'ends_at', 'venue', 'student_count', 'other_count'], true) => '3. Schedule and participants',
                    str_starts_with($key, 'conditions.') || $key === 'personnel' => '4. Risk conditions and personnel',
                    str_starts_with($key, 'preparation.') || $key === 'contracts' => '5. Preparation evidence and contracts',
                    str_starts_with($key, 'authorization.') => '6. HEI authorization',
                    default => '2. Activity purpose and classification',
                };
                $groups[$group][] = $field;
            }

            return [...array_map(
                fn (string $title, array $components) => Section::make($title)->schema($components)->columns(2)->columnSpanFull(),
                array_keys($groups),
                array_values($groups),
            ), ClassificationForms::section(), ClocaForms::activitySection(), ItineraryForms::activitySection(), DiagnosticForms::activitySection(), RacpForms::activitySection()];
        }

        if ($name === 'Document') {
            return [Section::make('Upload document')->schema(array_values(array_filter($fields, fn ($field) => ! str_starts_with($field->getName(), 'metadata.'))))->columns(2)->columnSpanFull(),
                Section::make('Form references and coverage (optional)')->schema(array_values(array_filter($fields, fn ($field) => str_starts_with($field->getName(), 'metadata.'))))->columns(2)->collapsible()->collapsed()->columnSpanFull()];
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
            'incident_draft' => app(SaveIncidentReportDraft::class)->execute($actor, $data),
            'change_draft' => app(SaveChangeReportDraft::class)->execute($actor, $data),
            'roc_draft' => app(SaveCompletionReportDraft::class)->execute($actor, $data),
            'certification' => app(SaveCertificationDraft::class)->execute($actor, $data),
            'saa' => app(SaveSaaDraft::class)->execute($actor, $data),
            'reference' => app(SaveReference::class)->execute($actor, $model, $data),
            'activity' => app(SaveActivity::class)->execute($actor, $data),
            'document' => app(UploadDocument::class)->execute($actor, (int) $data['institution_id'], (int) $data['document_type_id'], $data['file'], (bool) $data['is_signed'], $data['metadata'] ?? []),
            'completion','incident','change' => app(SubmitReport::class)->execute($actor, $type, $data),
            'monitoring','assistance','case','clearance','restriction' => app(RecordOperationalRecord::class)->execute($actor, $type, $data),
            default => throw new \LogicException('Creation is not available')
        };
    }
}
