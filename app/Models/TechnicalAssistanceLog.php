<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalAssistanceLog extends Model
{
    protected $table = 'technical_assistance_logs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['provided_on' => 'immutable_date'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }
}
