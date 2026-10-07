<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('change_reports', function (Blueprint $table) {
            $table->foreignId('change_report_draft_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->json('annex_i')->nullable();
            $table->foreignId('supplements_report_id')->nullable()->constrained('change_reports')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('change_report_draft_id');
            $table->dropColumn('annex_i');
            $table->dropConstrainedForeignId('supplements_report_id');
        });
    }
};
