<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $table = 'documents';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_signed' => 'boolean', 'metadata' => 'array'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function supersedesDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'supersedes_document_id');
    }

    protected static function booted(): void
    {
        static::updating(function ($model) {
            if (array_diff(array_keys($model->getDirty()), ['scan_status', 'updated_at'])) {
                throw new \LogicException('Document originals are immutable.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Official documents cannot be deleted.'));
    }
}
