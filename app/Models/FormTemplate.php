<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormTemplate extends Model
{
    protected $table = 'form_templates';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'active_from' => 'immutable_date', 'active_until' => 'immutable_date'];
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'policy_version_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
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
