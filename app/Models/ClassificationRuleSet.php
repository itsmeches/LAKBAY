<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Database\Factories\ClassificationRuleSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassificationRuleSet extends Model
{
    /** @use HasFactory<ClassificationRuleSetFactory> */
    use HasFactory;

    use Immutable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rules' => 'array', 'source' => 'array'];
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class);
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(ClassificationAssessment::class);
    }
}
