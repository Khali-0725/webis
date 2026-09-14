<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * One place for the `?trashed=` list filter every soft-deletable resource
 * exposes: `only` lists the trash, `with` lists everything, anything else
 * (the default) lists live rows only.
 */
final class TrashFilter
{
    public static function apply(Builder $query, Request $request): Builder
    {
        return match ($request->string('trashed')->toString()) {
            'only' => $query->onlyTrashed(),
            'with' => $query->withTrashed(),
            default => $query,
        };
    }
}
