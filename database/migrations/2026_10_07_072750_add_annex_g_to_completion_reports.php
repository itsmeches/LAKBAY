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
        Schema::table('completion_reports', function (Blueprint $table) {
            $table->foreignId('completion_report_draft_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->unsignedInteger('other_count')->nullable();
            $table->json('annex_g')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('completion_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completion_report_draft_id');
            $table->dropColumn(['other_count', 'annex_g']);
        });
    }
};
