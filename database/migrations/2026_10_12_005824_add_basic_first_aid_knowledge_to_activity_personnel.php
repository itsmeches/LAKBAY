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
        if (! Schema::hasColumn('activity_personnel', 'basic_first_aid_knowledge')) {
            Schema::table('activity_personnel', function (Blueprint $table) {
                $table->boolean('basic_first_aid_knowledge')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException('Personnel history is preserved; roll back code without dropping the additive column.');
    }
};
