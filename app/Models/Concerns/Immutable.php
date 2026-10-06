<?php

namespace App\Models\Concerns;

trait Immutable
{
    protected static function bootImmutable(): void
    {
        static::updating(function ($model) {
            throw new \LogicException('Historical records are immutable. Create a new revision.');
        });
        static::deleting(function ($model) {
            throw new \LogicException('Historical records cannot be deleted.');
        });
    }
}
