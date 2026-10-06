<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewFinding extends Model
{
    protected $table = 'review_findings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['resolved_at' => 'immutable_datetime'];
    }

    public function submissionReview(): BelongsTo
    {
        return $this->belongsTo(SubmissionReview::class, 'submission_review_id');
    }
}
