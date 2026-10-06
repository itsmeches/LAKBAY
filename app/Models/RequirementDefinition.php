<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementDefinition extends Model
{
    protected $table = 'requirement_definitions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
}
