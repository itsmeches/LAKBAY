<?php

namespace App\Models;

use Database\Factories\IncidentReportDraftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IncidentReportDraft extends Model
{
    /** @use HasFactory<IncidentReportDraftFactory> */
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

    public function activityVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class);
    }

    public function supplementsReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class, 'supplements_report_id');
    }

    public function incidentReport(): HasOne
    {
        return $this->hasOne(IncidentReport::class);
    }
}
