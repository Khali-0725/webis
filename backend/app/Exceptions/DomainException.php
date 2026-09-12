<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by service classes when a business rule is violated.
 *
 * This is the only exception type controllers and services should raise for
 * expected, user-facing failures (invalid state transition, slot taken,
 * already reviewed, ...). It renders straight into the failure envelope.
 */
class DomainException extends Exception
{
    /** @var array<string, mixed> */
    protected array $errors;

    protected int $status;

    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(string $message, int $status = 422, array $errors = [])
    {
        parent::__construct($message);

        $this->status = $status;
        $this->errors = $errors;
    }

    /**
     * 409 - the request conflicts with the record's current state.
     *
     * @param  array<string, mixed>  $errors
     */
    public static function conflict(string $message, array $errors = []): self
    {
        return new self($message, 409, $errors);
    }

    /**
     * 422 - the request is well-formed but breaks a business rule.
     *
     * @param  array<string, mixed>  $errors
     */
    public static function unprocessable(string $message, array $errors = []): self
    {
        return new self($message, 422, $errors);
    }

    /**
     * 403 - the caller is authenticated but not permitted.
     */
    public static function forbidden(string $message): self
    {
        return new self($message, 403);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
