<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityClassification extends Model
{
    use Immutable;

    protected $table = 'activity_classifications';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function activityVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'activity_version_id');
    }
}
