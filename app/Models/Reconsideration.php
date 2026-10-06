<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reconsideration extends Model
{
    protected $table = 'reconsiderations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['filed_at' => 'immutable_datetime', 'due_at' => 'immutable_datetime', 'is_late' => 'boolean'];
    }

    public function clearanceRequest(): BelongsTo
    {
        return $this->belongsTo(ClearanceRequest::class, 'clearance_request_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function decisionDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'decision_document_id');
    }
}
