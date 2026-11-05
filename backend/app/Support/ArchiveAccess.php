<?php

namespace App\Support;

use App\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class ArchiveAccess
{
    public static function listing(Request $request): void
    {
        if ($request->is('api/admin/*', 'api/bookings', 'api/payments')
            && in_array($request->query('trashed'), ['only', 'with'], true)
            && ! $request->is('api/admin/reviews')
            && ! $request->user()?->isSuperAdmin()) {
            throw DomainException::forbidden('Only a Super Administrator may view the archive.');
        }
    }

    public static function record(Model $record, ?Request $request = null): void
    {
        $request ??= request();
        if ($record->trashed() && ! $request->user()?->isSuperAdmin()) {
            throw DomainException::forbidden('Only a Super Administrator may view archived records.');
        }
    }

    public static function restore(Request $request): void
    {
        if (! $request->user()?->isSuperAdmin()) {
            throw DomainException::forbidden('Only a Super Administrator may restore archived records.');
        }
    }
}
