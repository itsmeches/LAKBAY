<?php

namespace Database\Seeders;

use App\Models\ClassificationRuleSet;
use Illuminate\Database\Seeder;

class ClassificationEvaluationRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(ClassificationRuleSetSeeder::class);
        $source = ClassificationRuleSet::where('code', 'CMO24-GAP2-PROPOSED-V1')->firstOrFail();
        $rules = $source->rules;
        $bindings = [
            'schedule' => ['starts_at', 'ends_at', 'departure_at', 'return_at', 'cloca.duration_days', 'itinerary.*.starts_at'],
            'scope' => ['title', 'description', 'learning_objectives', 'cloca.brief_description'],
            'design' => ['academic_nature', 'activity_type', 'initiator', 'itinerary.*.description'],
            'venue' => ['venue'], 'locations' => ['cloca.locations', 'itinerary.*.location', 'itinerary.*.locality'],
            'personnel' => ['personnel.*.name', 'personnel.*.role', 'personnel.*.position', 'itinerary.*.responsible_person'],
            'participant_count' => ['student_count', 'other_count'], 'participant_profile' => ['conditions.vulnerable_participants'],
            'cost' => ['conditions.has_student_costs', 'preparation.consultation', 'preparation.expense_breakdown'],
            'transport' => ['conditions.requires_travel', 'preparation.transport'],
            'accommodation' => ['conditions.requires_accommodation'],
            'physical_activity' => ['conditions.strenuous', 'conditions.requires_physical_activity'],
            'long_distance' => ['conditions.long_distance'], 'parallel_plan' => ['preparation.parallel_activity'],
            'invitation' => ['conditions.external_invitation'], 'coordination' => ['cloca.agency_coordination'],
            'assistance' => ['cloca.requested_assistance'], 'accreditation_or_contract_evidence' => ['contracts', 'itinerary.*.dot_accreditation'],
            'organizer_or_program' => ['responsible_office', 'program'],
            'contact_or_print_metadata' => ['personnel.*.contact', 'annex_j'],
            'third_party_services' => ['conditions.has_third_party_services'],
            'identity' => ['institution_id', 'academic_period_id', 'calendar_id', 'campus_id'],
        ];
        foreach ($bindings as $group => $paths) {
            $rules['materiality'][$group]['paths'] = $paths;
        }
        $rules['materiality']['invitation']['automatic_context_predicate'] = 'original_calendar_unchanged_schedule_scope_design';
        $rules['materiality']['invitation']['automatic_context_outcome'] = 'record_only';
        $rules['eligibility'] = ['confirmation_permission' => 'submissions.submit',
            'category_1' => ['requires_planned_before_year' => true, 'requires_regular_process' => true, 'requires_calendar' => true],
            'category_3' => ['requires_supervision_reference' => true, 'requires_explicit_trigger_facts' => true],
            'source_reference' => 'CMO No. 24 §§12–13, PDF pp.5–7'];
        $rules['revision'] = ['category_2_outcome' => 'category_2_and_rcd', 'unsubmitted_outcome' => 'record_only',
            'rcd_outcomes' => ['category_2_and_rcd', 'rcd_only'],
            'new_proposal_requirement_category' => 2, 'retain_declared_category' => true,
            'contextual_choices' => ['venue' => ['same_destination_address_correction'], 'transport' => ['equivalent_provider'],
                'participant_count' => ['scope_or_design_changed'], 'cost' => ['scope_or_design_changed'],
                'parallel_plan' => ['scope_or_design_changed'], 'organizer_or_program' => ['scope_or_design_changed'],
                'third_party_services' => ['scope_or_design_changed'], 'accreditation_or_contract_evidence' => ['operational_details_changed'],
                'contact_or_print_metadata' => ['safety_contact_changed']],
            'source_reference' => 'CMO No. 24 §13.B.3, PDF p.6; §16, PDF p.12; Annex I, PDF p.29'];
        $rules['timing']['lead_type'] = 'working_days_before';
        $rules['timing']['lead_amount'] = 3;
        $rules['timing']['lead_reference'] = 'CMO No. 24 §14, PDF pp.7–12; each evidence deadline remains separate';
        $rules['reversion']['restore_original_treatment'] = true;
        $rules['reversion']['reassess_removed_triggers'] = true;
        ClassificationRuleSet::firstOrCreate(['code' => 'CMO24-GAP2-EVALUATION-V2'], [
            'supersedes_id' => $source->id, 'policy_version_id' => $source->policy_version_id,
            'status' => 'proposed', 'rules' => $rules, 'source' => $source->source,
            'decision_source' => $source->decision_source.' Slice 2 supplies bounded field bindings/confirmation; no activation.',
        ]);
    }
}
