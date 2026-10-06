<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalReport extends Model
{
    use Immutable;

    protected $table = 'regional_reports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['cutoff_at' => 'immutable_datetime', 'filters' => 'array', 'totals' => 'array', 'sent_to_osds_at' => 'immutable_datetime'];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
