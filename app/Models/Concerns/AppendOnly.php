<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * For history records (treatment events, clinical notes, audit logs) that
 * must never be changed or removed once written. Corrections are new rows.
 */
trait AppendOnly
{
    public static function bootAppendOnly(): void
    {
        static::updating(function ($model) {
            throw new LogicException(class_basename($model).' records are append-only and cannot be updated.');
        });

        static::deleting(function ($model) {
            throw new LogicException(class_basename($model).' records are append-only and cannot be deleted.');
        });
    }
}
