<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_versions', fn (Blueprint $t) => $t->string('confirmation_reference')->nullable());
        Schema::create('document_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('institution_id')->constrained()->restrictOnDelete();
            $t->foreignId('submission_id')->constrained()->restrictOnDelete();
            $t->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $t->text('reason');
            $t->timestamp('due_at');
            $t->string('status')->default('requested');
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('response_document_id')->nullable()->constrained('documents')->restrictOnDelete();
            $t->timestamp('responded_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
        Schema::table('policy_versions', fn (Blueprint $t) => $t->dropColumn('confirmation_reference'));
    }
};
