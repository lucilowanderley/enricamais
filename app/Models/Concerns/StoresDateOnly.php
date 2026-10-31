<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

/**
 * Garante que colunas do tipo DATE sejam gravadas como "AAAA-MM-DD" (sem hora),
 * para que filtros por período funcionem igual no SQLite e no MySQL.
 */
trait StoresDateOnly
{
    protected static function dateOnlyAttribute(): Attribute
    {
        return Attribute::set(fn ($value) => $value === null || $value === '' ? null : Carbon::parse($value)->toDateString());
    }
}
