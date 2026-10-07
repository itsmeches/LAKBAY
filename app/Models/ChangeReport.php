<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeReport extends Model
{
    use Immutable;

    protected $table = 'change_reports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['annex_i' => 'array', 'deadline_parameters' => 'array', 'changed_at' => 'immutable_datetime', 'notified_at' => 'immutable_datetime', 'due_at' => 'immutable_datetime', 'is_late' => 'boolean'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function originalVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'original_version_id');
    }

    public function revisedVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'revised_version_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function supplementsReport(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supplements_report_id');
    }

    public function changeReportDraft(): BelongsTo
    {
        return $this->belongsTo(ChangeReportDraft::class);
    }
}
