<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompletionReport extends Model
{
    use Immutable;

    protected $table = 'completion_reports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['deadline_parameters' => 'array', 'ended_at' => 'immutable_datetime', 'due_at' => 'immutable_datetime', 'is_late' => 'boolean', 'incidents_encountered' => 'boolean'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
