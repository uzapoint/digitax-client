<?php

namespace Uzapoint\Digitax\Exceptions;

/**
 * DigiTax could not be reached (DNS, TLS, timeout). The request may or may
 * not have been processed; for POSTs, reconcile before retrying.
 */
class DigitaxConnectionException extends DigitaxException
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("DigiTax unreachable ({$method} {$path}): {$message}", 0, $previous);
    }

    public function isRetryable(): bool
    {
        return true;
    }
}
