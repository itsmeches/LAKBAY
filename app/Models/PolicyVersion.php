<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyVersion extends Model
{
    protected $table = 'policy_versions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['effective_on' => 'immutable_date', 'publication_on' => 'immutable_date', 'issued_on' => 'immutable_date', 'confirmed_at' => 'immutable_datetime', 'parameters' => 'array'];
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(PolicyRequirement::class);
    }

    protected static function booted(): void
    {
        static::updating(function ($model) {
            if ($model->getOriginal('status') === 'confirmed') {
                throw new \LogicException('Confirmed policy is immutable.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Policy history cannot be deleted.'));
    }
}
