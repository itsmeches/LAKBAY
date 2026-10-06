<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentPerson extends Model
{
    use Immutable;

    protected $table = 'incident_persons';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function incidentReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class, 'incident_report_id');
    }
}
