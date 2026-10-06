<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acknowledgment extends Model
{
    use Immutable;

    protected $table = 'acknowledgments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['letter_date' => 'immutable_date', 'summary' => 'array'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
