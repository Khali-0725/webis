<?php

namespace App\Support\Api;

use App\Exceptions\DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Converts every throwable into the WEBIS failure envelope.
 *
 * Production responses never leak stack traces, SQL, file paths or class names.
 * When APP_DEBUG=true a `debug` key is attached for local development only.
 */
final class ExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null; // let the default web handler deal with it
            }

            /*
             * Some exceptions already carry the exact response they want sent -
             * most importantly HttpResponseException, which is what a rate
             * limiter's `response()` callback is wrapped in. Laravel runs these
             * render callbacks BEFORE it unwraps that response, so returning a
             * response here would discard the limiter's 429 and replace it with
             * a generic 500. Hand those straight back to the framework.
             */
            if ($e instanceof HttpResponseException) {
                return null;
            }

            return self::toResponse($e);
        });
    }

    private static function toResponse(Throwable $e)
    {
        [$status, $message, $errors] = self::classify($e);

        $payload = [
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ];

        if (config('app.debug')) {
            $payload['debug'] = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ];
        }

        return response()->json($payload, $status);
    }

    /**
     * @return array{0:int,1:string,2:array<string,mixed>}
     */
    private static function classify(Throwable $e): array
    {
        if ($e instanceof ValidationException) {
            // Honour a custom status - ValidationException::withMessages(...)->status(429)
            // is how the sign-in throttle reports itself with field-level detail.
            $status = $e->status ?: 422;

            return [$status, self::defaultMessageFor($status), $e->errors()];
        }

        if ($e instanceof DomainException) {
            return [$e->getStatus(), $e->getMessage(), $e->getErrors()];
        }

        if ($e instanceof AuthenticationException) {
            return [401, 'You must be signed in to do that.', []];
        }

        if ($e instanceof AuthorizationException) {
            return [403, $e->getMessage() !== '' && $e->getMessage() !== 'This action is unauthorized.'
                ? $e->getMessage()
                : 'You are not allowed to perform this action.', []];
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return [404, 'The requested resource was not found.', []];
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return [429, 'Too many attempts. Please wait a moment and try again.', []];
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return [$status, self::defaultMessageFor($status), []];
        }

        return [500, 'Something went wrong on our end. Please try again.', []];
    }

    private static function defaultMessageFor(int $status): string
    {
        return match ($status) {
            400 => 'The request could not be understood.',
            401 => 'You must be signed in to do that.',
            403 => 'You are not allowed to perform this action.',
            404 => 'The requested resource was not found.',
            405 => 'That action is not supported on this endpoint.',
            409 => 'That action conflicts with the current state of the record.',
            413 => 'The uploaded file is too large.',
            419 => 'Your session has expired. Please refresh and try again.',
            422 => 'The submitted data is invalid.',
            429 => 'Too many attempts. Please wait a moment and try again.',
            503 => 'The service is temporarily unavailable.',
            default => 'The request could not be completed.',
        };
    }
}
