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
        Schema::table('case_events', function (Blueprint $table) {
            if (! Schema::hasColumn('case_events', 'procedure')) {
                $table->json('procedure')->nullable();
            }
            if (! Schema::hasColumn('case_events', 'event_key')) {
                $table->uuid('event_key')->nullable()->unique();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException('Case procedural history cannot be removed by rollback.');
    }
};
