<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentReport extends Model
{
    use Immutable;

    protected $table = 'incident_reports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['deadline_parameters' => 'array', 'occurred_at' => 'immutable_datetime', 'due_at' => 'immutable_datetime', 'is_late' => 'boolean', 'provisional_deadline' => 'boolean'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function supplementsReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class, 'supplements_report_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function persons(): HasMany
    {
        return $this->hasMany(IncidentPerson::class);
    }
}
