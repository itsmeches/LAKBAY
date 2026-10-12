<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\FormTemplate;
use App\Models\PolicyRequirement;
use App\Models\PolicyVersion;
use App\Models\Region;
use App\Models\RequirementDefinition;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['panel.hei', 'panel.ched', 'records.view', 'activities.manage', 'submissions.submit', 'submissions.review', 'acknowledgments.issue', 'documents.upload', 'reports.submit', 'monitoring.manage', 'cases.sensitive', 'incidents.view', 'institutions.manage', 'users.invite', 'reports.export', 'policy.manage'];
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $roles = [
            'hei_preparer' => ['panel.hei', 'records.view', 'activities.manage', 'documents.upload'],
            'hei_submitter' => ['panel.hei', 'records.view', 'activities.manage', 'documents.upload', 'submissions.submit', 'reports.submit'],
            'ched_records' => ['panel.ched', 'records.view', 'documents.upload'],
            'ched_reviewer' => ['panel.ched', 'records.view', 'submissions.review', 'documents.upload'],
            'ched_signatory' => ['panel.ched', 'records.view', 'acknowledgments.issue', 'documents.upload'],
            'ched_monitor' => ['panel.ched', 'records.view', 'monitoring.manage', 'documents.upload'],
            'ched_case_officer' => ['panel.ched', 'records.view', 'cases.sensitive', 'incidents.view', 'documents.upload'],
            'ched_analyst' => ['panel.ched', 'records.view', 'reports.export'],
            'system_administrator' => ['panel.ched', 'records.view', 'institutions.manage', 'users.invite', 'policy.manage']];
        foreach ($roles as $name => $p) {
            Role::findOrCreate($name, 'web')->syncPermissions($p);
        }
        Region::firstOrCreate(['code' => 'IV-A'], ['name' => 'CALABARZON']);
        $policy = PolicyVersion::firstOrCreate(['code' => 'LAKBAY-JULY-2026-DRAFT'], ['title' => 'July 2026 LAKBAY draft — pending CHED confirmation', 'status' => 'draft']);
        $types = [
            'cloca' => ['Calendar of Local Off-Campus Activities', 'B', 20],
            'saa' => ['Sworn Affidavit of Accountability', 'C', 21],
            'diagnostic' => ['Diagnostic Tool', 'D', 22],
            'racp' => ['Risk Assessment and Contingency Plan', 'E', 23],
            'certification' => ['Certification of Compliance', 'F', 24],
            'completion' => ['Report of Completion', 'G', 25],
            'incident' => ['Incident Report', 'H', 26],
            'change' => ['Report of Change of Details', 'I', 27],
            'itinerary' => ['Detailed Itinerary', 'J', 28],
            'acknowledgment' => ['CHEDRO Acknowledgment', 'K', 29],
            'consent' => ['Sample Consent Form', 'H (duplicate)', 30],
            'monitoring' => ['Monitoring and Evaluation', 'I (duplicate)', 31],
            'padp' => ['Parallel Activity Design and Plan', null, null],
            'pic_designation' => ['PIC Designation', null, 9],
            'parent_notice' => ['Parent or Guardian Notice', null, 10],
            'invitation' => ['External Invitation', null, 10],
            'lgu_coordination' => ['LGU Coordination', null, 10],
            'dot_accreditation' => ['DOT Accreditation Evidence', null, 10],
            'orientation' => ['Pre-departure Orientation Evidence', null, 10],
            'medical_permission' => ['Permission, Medical Clearance and Insurance', null, 11],
            'official_order' => ['Signed Official Order', null, 16],
            'ceb_decision' => ['Signed CEB Decision', null, 13],
            'reconsideration' => ['Motion for Reconsideration', null, 13],
            'supporting' => ['Supporting Evidence', null, null]];
        foreach ($types as $code => [$title,$annex,$page]) {
            $type = DocumentType::firstOrCreate(['code' => $code], ['name' => $title, 'annex' => $annex, 'source_page' => $page, 'confidentiality' => in_array($code, ['official_order', 'ceb_decision', 'reconsideration']) ? 'restricted' : (in_array($code, ['incident', 'medical_permission', 'consent']) ? 'sensitive' : 'institutional')]);
            RequirementDefinition::firstOrCreate(['code' => $code], ['title' => $title, 'document_type_id' => $type->id]);
            if ($annex) {
                FormTemplate::firstOrCreate(['policy_version_id' => $policy->id, 'code' => $code, 'version' => 1], ['title' => $title, 'annex' => $annex, 'source_page' => $page]);
            }
        }
        if ($policy->status === 'draft' && ! $policy->requirements()->exists()) {
            foreach ([1, 2, 3] as $cat) {
                $rules = [['certification', 'always', 'submitted']];
                if ($cat === 1) {
                    $rules = array_merge($rules, [['cloca', 'always', 'submitted'], ['saa', 'always', 'submitted'], ['padp', 'always', 'submitted']]);
                }
                if ($cat === 2) {
                    $rules = array_merge($rules, [['saa', 'always', 'submitted'], ['padp', 'always', 'submitted'], ['pic_designation', 'always', 'submitted']]);
                }
                if ($cat !== 1) {
                    $rules = array_merge($rules, [['itinerary', 'always', 'submitted'], ['parent_notice', 'always', 'submitted'], ['diagnostic', 'always', 'certified_retained'], ['racp', 'always', 'certified_retained'], ['invitation', 'external_invitation', 'submitted'], ['lgu_coordination', 'always', 'submitted'], ['dot_accreditation', 'requires_accommodation', 'submitted'], ['orientation', 'always', 'submitted'], ['medical_permission', 'higher_risk', 'certified_retained']]);
                }
                foreach ($rules as [$code,$predicate,$mode]) {
                    PolicyRequirement::create(['policy_version_id' => $policy->id, 'requirement_definition_id' => RequirementDefinition::where('code', $code)->value('id'), 'category' => $cat, 'predicate' => $predicate, 'evidence_mode' => $mode, 'deadline_type' => $cat === 1 ? 'calendar_days_before' : 'working_days_before', 'deadline_anchor' => $cat === 1 ? 'academic_year_start' : 'activity_start', 'deadline_amount' => $cat === 1 ? 30 : 3, 'source_reference' => 'July 2026 draft, pp. 7-11; verify applicability before production']);
                }
            }
        }
        $this->call(Cmo24PolicySeeder::class);
        $this->call(CaseEvidenceTypeSeeder::class);
    }
}
