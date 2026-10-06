<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['completion_reports', 'incident_reports', 'change_reports'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('policy_version_id')->nullable()->constrained()->restrictOnDelete();
                $t->string('holiday_calendar_version')->nullable();
                $t->json('deadline_parameters')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['completion_reports', 'incident_reports', 'change_reports'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('policy_version_id');
                $t->dropColumn(['holiday_calendar_version', 'deadline_parameters']);
            });
        }
    }
};
