<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionRequirement extends Model
{
    use Immutable;

    protected $table = 'submission_requirements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['document_ids' => 'array', 'rule_snapshot' => 'array', 'due_at' => 'immutable_datetime'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function policyRequirement(): BelongsTo
    {
        return $this->belongsTo(PolicyRequirement::class, 'policy_requirement_id');
    }

    public function activityVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'activity_version_id');
    }
}
