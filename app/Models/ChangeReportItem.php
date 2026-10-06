<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeReportItem extends Model
{
    use Immutable;

    protected $table = 'change_report_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function changeReport(): BelongsTo
    {
        return $this->belongsTo(ChangeReport::class, 'change_report_id');
    }
}
