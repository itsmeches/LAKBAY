<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityPersonnel extends Model
{
    use Immutable;

    protected $table = 'activity_personnel';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['first_aid_trained' => 'boolean', 'hei_employed' => 'boolean', 'accompanies_activity' => 'boolean'];
    }

    public function activityVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'activity_version_id');
    }
}
