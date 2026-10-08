<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\FormTemplate;
use App\Models\PolicyRequirement;
use App\Models\PolicyVersion;
use App\Models\RequirementDefinition;
use App\Services\Compliance\PolicyRules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Cmo24PolicySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $policy = PolicyVersion::firstOrCreate(['code' => PolicyRules::CMO_CODE], [
                'title' => 'CMO No. 24, s. 2026 — activation pending publication verification',
                'status' => 'candidate', 'issued_on' => '2026-09-28',
                'source_sha256' => 'a7915e3eea2f89a6a5cb63ac83ce3947aa40abbd79bf42499aa4a17d22174c01',
                'parameters' => ['source_cmo' => 24, 'incident_hours' => 48, 'completion_hours' => 72, 'change_hours' => 72, 'change_anchor' => 'unresolved',
                    'clearance_days_before' => 90, 'clearance_day_unit' => 'calendar_provisional', 'reconsideration_days' => 15,
                    'transition_academic_year' => '2026-2027', 'transition_days_after_publication' => 30,
                    'effectivity_days_after_publication' => 15, 'monitoring_version' => 'CMO24-2026-Annex-M',
                    'annual_report_window' => 'Last week of August'],
            ]);
            if ($policy->requirements()->exists() || $policy->status === 'confirmed') {
                return;
            }
            foreach ([
                'consultation' => ['Consultation and expense breakdown evidence', 'institutional'],
                'first_aid' => ['First-aid training evidence', 'institutional'],
                'insurance' => ['Insurance coverage evidence', 'sensitive'],
                'third_party_contract' => ['Third-party service contract', 'institutional'],
                'complaint' => ['Signed written complaint', 'restricted'],
                'clearance_application' => ['Exceptional clearance application', 'restricted'],
                'clearance_correspondence' => ['CHEDRO / OSDS clearance correspondence', 'restricted'],
                'process_flow' => ['Local Off-Campus Activity Process Flow', 'institutional'],
            ] as $code => [$name, $confidentiality]) {
                DocumentType::firstOrCreate(['code' => $code], compact('name', 'confidentiality'));
            }
            $templates = [
                'cloca' => ['B', 21, ['academic_year', 'semester', 'activity_type_code', 'within_outside_region', 'agency_assistance', 'prepared_by', 'reviewed_by', 'noted_by']],
                'saa' => ['C', 22, ['semester', 'academic_year', 'affiant', 'authority', 'hei_head', 'notarial_reference']],
                'diagnostic' => ['D', 23, ['assessment', 'recommendations', 'prepared_by', 'reviewed_by', 'noted_by']],
                'racp' => ['E', 24, ['risk_levels', 'contingencies', 'emergency_contacts', 'prepared_by', 'reviewed_by', 'noted_by']],
                'certification' => ['F', 25, ['applicable_requirements', 'evidence_references', 'official', 'position', 'date', 'place']],
                'completion' => ['G', 27, ['actual_conclusion', 'implementation', 'change_explanation', 'incident_references', 'certifying_official']],
                'incident' => ['H', 28, ['occurred_at', 'location', 'persons', 'actions', 'status', 'supporting_references', 'certifying_official']],
                'change' => ['I', 29, ['original_submission_reference', 'previous_revised_details', 'pic_apic', 'participant_count', 'reason', 'prepared_by', 'reviewed_by', 'noted_by']],
                'itinerary' => ['J', 30, ['date', 'time', 'activity', 'venue', 'dot_information', 'responsible_person', 'prepared_by', 'reviewed_by', 'noted_by']],
                'acknowledgment' => ['K', 31, ['received_date', 'program_course', 'period_covered', 'activity_count', 'student_participations', 'director_signature']],
                'consent' => ['L', 32, ['activity', 'student_consent', 'guardian_acknowledgment', 'emergency_contact']],
                'monitoring' => ['M', 33, ['region', 'period', 'monitoring_date', 'type', 'respondent_position', 'findings', 'prepared_by', 'reviewed_by', 'hei_conforme']],
                'process_flow' => ['N', 35, ['hei_process', 'chedro_process', 'osds_process']],
            ];
            foreach ($templates as $code => [$annex, $page, $fields]) {
                FormTemplate::firstOrCreate(['policy_version_id' => $policy->id, 'code' => $code, 'version' => 1], [
                    'title' => DocumentType::where('code', $code)->firstOrFail()->name,
                    'annex' => $annex, 'source_page' => $page, 'metadata' => ['fields' => $fields],
                ]);
            }
            $rows = [
                1 => ['Mission and vision alignment', 'certification', [1, 2, 3], 'always', 7],
                2 => ['Academic / representation / student well-being purpose', 'certification', [1, 2, 3], 'always', 8],
                3 => ['Inclusion in the academic calendar', 'certification', [1], 'always', 8],
                4 => ['Completed CLOCA and certification', 'cloca', [1, 2], 'always', 8],
                5 => ['Parallel Activity Design and Plan', 'padp', [1, 2], 'always', 8],
                6 => ['Cost consultation and expense breakdown', 'certification', [1, 2, 3], 'has_student_costs', 9],
                7 => ['External invitation', 'invitation', [2, 3], 'external_invitation', 9],
                8 => ['LGU coordination', 'lgu_coordination', [2, 3], 'always', 9],
                9 => ['Accommodation or venue DOT accreditation information', 'dot_accreditation', [2, 3], 'venue_accreditation', 9],
                10 => ['Sworn Affidavit of Accountability', 'saa', [1, 2], 'always', 9],
                11 => ['Authorized HEI approval', 'certification', [1, 2], 'always', 9],
                12 => ['PIC designation, first-aid training and ratio', 'pic_designation', [1, 2], 'always', 10],
                13 => ['RACP certification and retained assessment', 'certification', [1, 2, 3], 'always', 10],
                14 => ['Diagnostic certification and retained assessment', 'certification', [1, 2, 3], 'always', 10],
                15 => ['Relevant permission, medical clearance and insurance certification', 'certification', [2, 3], 'medical_risk', 10],
                16 => ['Detailed itinerary', 'itinerary', [1, 2, 3], 'always', 11],
                17 => ['Written parent / guardian / spouse notification', 'parent_notice', [1, 2, 3], 'always', 11],
                18 => ['Pre-departure orientation with evidence', 'certification', [2, 3], 'always', 11],
                19 => ['Supervising faculty / student affairs approval', 'certification', [3], 'always', 11],
                20 => ['Report of completion', 'completion', [1, 2, 3], 'always', 11],
                21 => ['Incident report, when applicable', 'incident', [1, 2, 3], 'has_incident', 11],
                22 => ['Report of changes, when applicable', 'change', [1, 2, 3], 'has_changes', 12],
            ];
            foreach ($rows as $row => [$title, $document, $categories, $predicate, $page]) {
                foreach ($categories as $category) {
                    $annual = $category === 1 && in_array($row, [1, 2, 3, 4, 5, 6, 10, 11, 12], true);
                    $post = $row >= 20;
                    $unresolved = ($category === 3 && in_array($row, [1, 2, 6], true)) || $row === 22;
                    $this->rule($policy, 'cmo24_row_'.$row, $title, $document, $row, $category, [
                        'predicate' => $predicate, 'stage' => $post ? 'post_activity' : ($annual ? 'annual_calendar' : 'pre_activity'),
                        'signature_mode' => in_array($row, [7, 8, 9, 12, 17], true) ? 'copy' : 'signed',
                        'supporting_document_codes' => match ($row) {
                            4 => ['certification'], 6 => ['consultation'], 12 => ['first_aid'], 18 => ['orientation'], default => []
                        },
                        'evidence_mode' => 'submitted',
                        'deadline_type' => $post ? 'hours_after' : ($annual ? 'calendar_days_before' : 'working_days_before'),
                        'deadline_anchor' => $post ? match ($row) {
                            20 => 'actual_conclusion', 21 => 'incident_occurrence', 22 => 'unresolved'
                        } : ($annual ? 'academic_year_start' : 'activity_start'),
                        'deadline_amount' => $post ? ($row === 21 ? 48 : 72) : ($annual ? 30 : 3),
                        'deadline_status' => $unresolved ? 'unresolved' : 'confirmed',
                        'source_reference' => 'CMO 24 s.2026 §14 row '.$row.', PDF p.'.$page,
                    ]);
                }
            }
            foreach ([13 => ['racp'], 14 => ['diagnostic'], 15 => ['consent', 'medical_permission', 'insurance']] as $row => $documents) {
                foreach ($documents as $document) {
                    foreach ($row === 15 ? [2, 3] : [1, 2, 3] as $category) {
                        $this->rule($policy, 'cmo24_requested_'.$document, 'Retained '.$document.' — immediately on CHED request', $document, $row, $category, [
                            'stage' => 'requested_evidence', 'predicate' => $row === 15 ? 'medical_risk' : 'always',
                            'evidence_mode' => 'certified_retained', 'signature_mode' => $document === 'insurance' ? 'copy' : 'signed',
                            'deadline_type' => 'on_request', 'deadline_anchor' => 'request_receipt', 'deadline_amount' => 0,
                            'deadline_status' => 'on_request', 'source_reference' => 'CMO 24 s.2026 §14 row '.$row.', PDF pp.10–11',
                        ]);
                    }
                }
            }
        });
    }

    private function rule(PolicyVersion $policy, string $code, string $title, string $document, int $row, int $category, array $attributes): void
    {
        $definition = RequirementDefinition::firstOrCreate(['code' => $code], ['title' => $title, 'document_type_id' => DocumentType::where('code', $document)->firstOrFail()->id]);
        PolicyRequirement::create($attributes + ['policy_version_id' => $policy->id, 'requirement_definition_id' => $definition->id, 'section_row' => $row, 'category' => $category]);
    }
}
