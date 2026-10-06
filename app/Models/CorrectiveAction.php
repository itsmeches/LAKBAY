<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectiveAction extends Model
{
    protected $table = 'corrective_actions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['due_on' => 'immutable_date'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function monitoringRecord(): BelongsTo
    {
        return $this->belongsTo(MonitoringRecord::class, 'monitoring_record_id');
    }
}
