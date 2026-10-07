<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalReport extends Model
{
    protected $table = 'regional_reports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['cutoff_at' => 'immutable_datetime', 'due_at' => 'immutable_datetime', 'filters' => 'array', 'totals' => 'array', 'sent_to_osds_at' => 'immutable_datetime', 'osds_received_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function ($model) {
            if (array_diff(array_keys($model->getDirty()), ['document_id', 'sent_to_osds_at', 'dispatch_reference', 'osds_received_at', 'receipt_reference', 'updated_at'])) {
                throw new \LogicException('Regional reporting snapshots are immutable.');
            }
            foreach (['document_id', 'sent_to_osds_at', 'dispatch_reference', 'osds_received_at', 'receipt_reference'] as $field) {
                if ($model->isDirty($field) && $model->getOriginal($field) !== null) {
                    throw new \LogicException('Recorded dispatch and receipt cannot be overwritten.');
                }
            }
        });
        static::deleting(fn () => throw new \LogicException('Regional reports cannot be deleted.'));
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
