<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyRequirement extends Model
{
    protected $table = 'policy_requirements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'policy_version_id');
    }

    public function requirementDefinition(): BelongsTo
    {
        return $this->belongsTo(RequirementDefinition::class, 'requirement_definition_id');
    }

    protected static function booted(): void
    {
        foreach (['creating', 'updating', 'deleting'] as $event) {
            static::$event(function ($model) {
                if (PolicyVersion::find($model->policy_version_id)?->status === 'confirmed') {
                    throw new \LogicException('Confirmed policy components are immutable.');
                }
            });
        }
    }
}
