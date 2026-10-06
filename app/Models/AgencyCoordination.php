<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyCoordination extends Model
{
    use Immutable;

    protected $table = 'agency_coordinations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['coordinated_on' => 'immutable_date'];
    }

    public function activityVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'activity_version_id');
    }
}
