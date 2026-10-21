<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Shared ordering and date filters for administrator record lists. */
final class AdminRecordFilters
{
    public static function apply(Builder $query, Request $request): Builder
    {
        $filters = $request->validate([
            'sort' => ['nullable', 'in:newest,oldest'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $column = $request->string('trashed')->toString() === 'only' ? 'deleted_at' : 'created_at';

        if (! empty($filters['start_date'])) {
            $query->whereDate($column, '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate($column, '<=', $filters['end_date']);
        }

        $direction = ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc';

        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }
}
