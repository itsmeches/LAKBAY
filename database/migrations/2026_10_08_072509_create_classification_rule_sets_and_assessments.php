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
        if (! Schema::hasTable('classification_rule_sets')) {
            Schema::create('classification_rule_sets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('policy_version_id')->constrained()->restrictOnDelete();
                $table->foreignId('supersedes_id')->nullable()->constrained('classification_rule_sets')->restrictOnDelete();
                $table->string('code')->unique();
                $table->string('status')->default('proposed');
                $table->json('rules');
                $table->json('source');
                $table->text('decision_source');
                $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('classification_assessments')) {
            Schema::create('classification_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('activity_version_id')->constrained()->restrictOnDelete();
                $table->foreignId('previous_assessment_id')->nullable()->constrained('classification_assessments')->restrictOnDelete();
                $table->foreignId('baseline_version_id')->nullable()->constrained('activity_versions')->restrictOnDelete();
                $table->foreignId('baseline_submission_id')->nullable()->constrained('submissions')->restrictOnDelete();
                $table->foreignId('classification_rule_set_id')->constrained()->restrictOnDelete();
                $table->string('event_key')->unique();
                $table->string('state')->default('recommended');
                $table->unsignedTinyInteger('declared_category');
                $table->unsignedTinyInteger('recommended_category');
                $table->unsignedTinyInteger('confirmed_category')->nullable();
                foreach (['facts', 'changes', 'requirements_before', 'requirements_after', 'timing', 'source_references'] as $column) {
                    $table->json($column);
                }
                $table->text('reason');
                $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException('Classification history is preserved; roll back code without dropping additive tables.');
    }
};
