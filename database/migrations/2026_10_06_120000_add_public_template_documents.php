<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $t) {
            $t->foreignId('document_id')->nullable()->constrained()->restrictOnDelete();
            $t->boolean('is_public')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $t) {
            $t->dropConstrainedForeignId('document_id');
            $t->dropColumn('is_public');
        });
    }
};
