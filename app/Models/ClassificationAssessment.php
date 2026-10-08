<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Database\Factories\ClassificationAssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassificationAssessment extends Model
{
    /** @use HasFactory<ClassificationAssessmentFactory> */
    use HasFactory;

    use Immutable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['facts' => 'array', 'changes' => 'array', 'requirements_before' => 'array', 'requirements_after' => 'array',
            'timing' => 'array', 'source_references' => 'array', 'confirmed_at' => 'immutable_datetime', 'occurred_at' => 'immutable_datetime'];
    }

    public function activityVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class);
    }

    public function baselineVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'baseline_version_id');
    }

    public function baselineSubmission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'baseline_submission_id');
    }

    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(ClassificationRuleSet::class, 'classification_rule_set_id');
    }

    public function previousAssessment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_assessment_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
