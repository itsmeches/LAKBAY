<?php

namespace Database\Seeders;

use App\Models\ClassificationRuleSet;
use App\Models\PolicyVersion;
use Illuminate\Database\Seeder;

class ClassificationRuleSetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $policy = PolicyVersion::where('code', 'CMO24-2026')->firstOrFail();
        $decision = 'Project owner approval, 8 October 2026: gap #2 Slice 1; proposed defaults pending CHED confirmation. Not a CHED decision or policy activation.';
        $rules = [];
        foreach ([
            'schedule' => ['category_2_and_rcd', 'record_only', '13.B.3;16', '6,12', false],
            'scope' => ['category_2_and_rcd', 'record_only', '13.B.3;16', '6,12', false],
            'design' => ['category_2_and_rcd', 'record_only', '13.B.3;16', '6,12', false],
            'venue' => ['category_2_and_rcd', 'rcd_only', '13.B.3;16;Annex I', '6,12,29', true],
            'locations' => ['category_2_and_rcd', 'rcd_only', '13.B.3;16;Annex I', '6,12,29', true],
            'personnel' => ['rcd_only', 'record_only', '16;Annex I', '12,29', true],
            'participant_count' => ['rcd_only', 'category_2_and_rcd', '13.B.3;13.C.5;Annex I', '6,7,29', true],
            'participant_profile' => ['category_2_and_rcd', 'rcd_only', '13.B.3;13.C.5;16', '6,7,12', true],
            'cost' => ['rcd_only', 'category_2_and_rcd', '13.B.3;14 row 5;16', '6,9,12', true],
            'transport' => ['category_2_and_rcd', 'rcd_only', '13.B.3;16', '6,12', true],
            'accommodation' => ['category_2_and_rcd', 'rcd_only', '13.B.3;13.C.5;16', '6,7,12', true],
            'physical_activity' => ['category_2_and_rcd', 'record_only', '13.B.3;13.C.5;16', '6,7,12', true],
            'long_distance' => ['category_2_and_rcd', 'record_only', '13.B.3;13.C.5;16', '6,7,12', true],
            'parallel_plan' => ['rcd_only', 'category_2_and_rcd', '12.F;13.B.3;16', '5,6,12', true],
            'invitation' => ['category_2_and_rcd', 'record_only', '13.B.1–3', '6', true],
            'coordination' => ['rcd_only', 'record_only', '14 row 7;16;Annex I', '9,12,29', true],
            'assistance' => ['rcd_only', 'record_only', '16;Annex I', '12,29', true],
            'accreditation_or_contract_evidence' => ['record_only', 'rcd_only', '14 row 8;16;29', '9,12,14', true],
            'organizer_or_program' => ['rcd_only', 'category_2_and_rcd', '13.B.3;16;Annex I', '6,12,29', true],
            'contact_or_print_metadata' => ['record_only', 'rcd_only', '16;Annex I', '12,29', true],
            'third_party_services' => ['rcd_only', 'category_2_and_rcd', '13.B.3;16;29', '6,12,14', true],
            'identity' => ['new_preparation_required', 'new_preparation_required', '12;13;Annex I', '5–7,29', true],
        ] as $field => [$default, $alternative, $sections, $pages, $needsDecision]) {
            $rules['materiality'][$field] = ['default_outcome' => $default, 'contextual_outcome' => $alternative,
                'needs_ched_decision' => $needsDecision, 'decision_source' => $decision,
                'source_reference' => 'CMO No. 24, s. 2026 §§'.$sections.', PDF pp.'.$pages,
                'interpretation' => 'Use approved gap2-implementation-plan.md materiality row; no numeric or minor-change exemption. Context must be confirmed with a reason.'];
        }
        $rules['category_3_escalation'] = ['triggers' => ['strenuous', 'requires_accommodation', 'long_distance', 'vulnerable_participants', 'class_size'],
            'declared_category' => 3, 'requirement_category' => 2, 'source_reference' => 'CMO No. 24 §13.C.5, PDF p.7'];
        $rules['reversion'] = ['default_outcome' => 'authorized_confirmation_required', 'needs_ched_decision' => true,
            'decision_source' => $decision, 'source_reference' => 'CMO No. 24 §13.B.3, PDF p.6; §16, PDF p.12; Annex I, PDF p.29'];
        $rules['timing'] = ['rcd_anchor' => 'unresolved', 'rcd_hours' => 72, 'immediate_notice' => true,
            'late_intake' => 'accept_and_flag', 'source_reference' => 'CMO No. 24 §14 row 22 and §16, PDF p.12; §24, PDF p.13',
            'decision_source' => $decision, 'needs_ched_decision' => true];
        ClassificationRuleSet::firstOrCreate(['code' => 'CMO24-GAP2-PROPOSED-V1'], [
            'policy_version_id' => $policy->id, 'status' => 'proposed', 'rules' => $rules,
            'source' => ['file_name' => 'CMO NO. 24 S. 2026 (1).pdf', 'page_count' => 36, 'issued_on' => '2026-09-28',
                'sha256' => 'a7915e3eea2f89a6a5cb63ac83ce3947aa40abbd79bf42499aa4a17d22174c01',
                'publication' => 'unverified', 'reference' => 'CMO No. 24 §§12–13, PDF pp.5–7; §§16,22,24, PDF pp.12–13; Annex I, PDF p.29'],
            'decision_source' => $decision,
        ]);
    }
}
