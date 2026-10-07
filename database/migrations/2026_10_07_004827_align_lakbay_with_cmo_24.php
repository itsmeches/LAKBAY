<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_versions', function (Blueprint $t) {
            $t->date('issued_on')->nullable();
            $t->string('source_sha256', 64)->nullable();
            $t->string('publication_reference')->nullable();
            $t->json('parameters')->nullable();
        });
        Schema::table('policy_requirements', function (Blueprint $t) {
            $t->unsignedTinyInteger('section_row')->nullable();
            $t->string('stage')->default('pre_activity');
            $t->string('signature_mode')->default('signed');
            $t->string('deadline_status')->default('confirmed');
            $t->json('supporting_document_codes')->nullable();
        });
        Schema::table('form_templates', fn (Blueprint $t) => $t->json('metadata')->nullable());
        Schema::table('submissions', function (Blueprint $t) {
            $t->string('stage')->default('legacy');
            $t->timestamp('due_at')->nullable()->change();
        });
        Schema::table('submission_requirements', fn (Blueprint $t) => $t->json('rule_snapshot')->nullable());
        Schema::table('document_requests', function (Blueprint $t) {
            $t->string('signature_mode')->default('copy');
            $t->json('deadline_parameters')->nullable();
        });
        Schema::table('activity_versions', function (Blueprint $t) {
            $t->json('contracts')->nullable();
            $t->json('preparation')->nullable();
        });
        Schema::table('activity_personnel', function (Blueprint $t) {
            $t->boolean('hei_employed')->default(false);
            $t->boolean('accompanies_activity')->default(false);
            $t->string('first_aid_reference')->nullable();
        });
        Schema::table('completion_reports', function (Blueprint $t) {
            $t->text('change_explanation')->nullable();
            $t->json('incident_report_ids')->nullable();
        });
        Schema::table('incident_reports', fn (Blueprint $t) => $t->string('intake_kind')->default('legacy'));
        Schema::table('change_reports', function (Blueprint $t) {
            $t->timestamp('due_at')->nullable()->change();
            $t->boolean('is_late')->nullable()->change();
            $t->foreignId('document_id')->nullable()->change();
            $t->string('intake_kind')->default('legacy');
            $t->timestamp('notified_at')->nullable();
            $t->string('original_submission_reference')->nullable();
        });
        Schema::table('monitoring_records', function (Blueprint $t) {
            $t->string('criteria_version')->nullable();
            $t->json('annex_metadata')->nullable();
        });
        Schema::table('case_records', fn (Blueprint $t) => $t->foreignId('source_document_id')->nullable()->constrained('documents')->restrictOnDelete());
        Schema::table('clearance_requests', function (Blueprint $t) {
            $t->foreignId('policy_version_id')->nullable()->constrained()->restrictOnDelete();
            $t->timestamp('application_received_at')->nullable();
            $t->timestamp('application_due_at')->nullable();
            $t->boolean('application_is_late')->nullable();
            $t->foreignId('application_document_id')->nullable()->constrained('documents')->restrictOnDelete();
            $t->json('deadline_parameters')->nullable();
            $t->json('routing_history')->nullable();
            $t->string('decision_receipt_reference')->nullable();
        });
        Schema::table('reconsiderations', fn (Blueprint $t) => $t->string('filing_status')->default('recorded'));
        Schema::table('regional_reports', function (Blueprint $t) {
            $t->unsignedSmallInteger('reporting_year')->nullable();
            $t->string('report_type')->default('regional');
            $t->string('due_window')->nullable();
            $t->timestamp('due_at')->nullable();
            $t->string('dispatch_reference')->nullable();
            $t->timestamp('osds_received_at')->nullable();
            $t->string('receipt_reference')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('regional_reports', fn (Blueprint $t) => $t->dropColumn(['reporting_year', 'report_type', 'due_window', 'due_at', 'dispatch_reference', 'osds_received_at', 'receipt_reference']));
        Schema::table('reconsiderations', fn (Blueprint $t) => $t->dropColumn('filing_status'));
        Schema::table('clearance_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignId('policy_version_id');
            $t->dropConstrainedForeignId('application_document_id');
            $t->dropColumn(['application_received_at', 'application_due_at', 'application_is_late', 'deadline_parameters', 'routing_history', 'decision_receipt_reference']);
        });
        Schema::table('case_records', fn (Blueprint $t) => $t->dropConstrainedForeignId('source_document_id'));
        Schema::table('monitoring_records', fn (Blueprint $t) => $t->dropColumn(['criteria_version', 'annex_metadata']));
        Schema::table('change_reports', fn (Blueprint $t) => $t->dropColumn(['intake_kind', 'notified_at', 'original_submission_reference']));
        Schema::table('incident_reports', fn (Blueprint $t) => $t->dropColumn('intake_kind'));
        Schema::table('completion_reports', fn (Blueprint $t) => $t->dropColumn(['change_explanation', 'incident_report_ids']));
        Schema::table('activity_personnel', fn (Blueprint $t) => $t->dropColumn(['hei_employed', 'accompanies_activity', 'first_aid_reference']));
        Schema::table('activity_versions', fn (Blueprint $t) => $t->dropColumn(['contracts', 'preparation']));
        Schema::table('submission_requirements', fn (Blueprint $t) => $t->dropColumn('rule_snapshot'));
        Schema::table('document_requests', fn (Blueprint $t) => $t->dropColumn(['signature_mode', 'deadline_parameters']));
        Schema::table('submissions', fn (Blueprint $t) => $t->dropColumn('stage'));
        Schema::table('form_templates', fn (Blueprint $t) => $t->dropColumn('metadata'));
        Schema::table('policy_requirements', fn (Blueprint $t) => $t->dropColumn(['section_row', 'stage', 'signature_mode', 'deadline_status', 'supporting_document_codes']));
        Schema::table('policy_versions', fn (Blueprint $t) => $t->dropColumn(['issued_on', 'source_sha256', 'publication_reference', 'parameters']));
    }
};
