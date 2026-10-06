<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    protected $table = 'submissions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['submitted_at' => 'immutable_datetime', 'received_at' => 'immutable_datetime', 'due_at' => 'immutable_datetime', 'is_late' => 'boolean', 'snapshot' => 'array'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class, 'calendar_id');
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'policy_version_id');
    }

    public function supersedesSubmission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'supersedes_submission_id');
    }

    public function activityVersions(): BelongsToMany
    {
        return $this->belongsToMany(ActivityVersion::class, 'submission_activities');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(SubmissionRequirement::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SubmissionReview::class);
    }

    public function acknowledgment(): HasOne
    {
        return $this->hasOne(Acknowledgment::class);
    }

    protected static function booted(): void
    {
        static::updating(function ($model) {
            if (array_diff(array_keys($model->getDirty()), ['status', 'assigned_to', 'updated_at'])) {
                throw new \LogicException('Submission contents are immutable.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Submissions cannot be deleted.'));
    }
}
