<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClearanceRequest extends Model
{
    protected $table = 'clearance_requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['decided_at' => 'immutable_datetime', 'decision_received_at' => 'immutable_datetime', 'application_received_at' => 'immutable_datetime', 'application_due_at' => 'immutable_datetime', 'application_is_late' => 'boolean', 'deadline_parameters' => 'array', 'routing_history' => 'array'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function activityVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'activity_version_id');
    }

    public function caseRecord(): BelongsTo
    {
        return $this->belongsTo(CaseRecord::class, 'case_record_id');
    }

    public function decisionDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'decision_document_id');
    }

    public function reconsideration(): HasOne
    {
        return $this->hasOne(Reconsideration::class);
    }
}
