<?php

namespace App\Models;

use Database\Factories\ChangeReportDraftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChangeReportDraft extends Model
{
    /** @use HasFactory<ChangeReportDraftFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $attributes = ['revision' => 1];

    protected function casts(): array
    {
        return ['details' => 'array', 'context' => 'array'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function originalVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'original_version_id');
    }

    public function revisedVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'revised_version_id');
    }

    public function originalSubmission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'original_submission_id');
    }

    public function supplementsReport(): BelongsTo
    {
        return $this->belongsTo(ChangeReport::class, 'supplements_report_id');
    }

    public function changeReport(): HasOne
    {
        return $this->hasOne(ChangeReport::class);
    }
}
