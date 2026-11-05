<?php

namespace Uzapoint\Digitax\Exceptions;

/**
 * The business has eTIMS switched off, has no API key, or has no active
 * etims-integration addon. Callers should skip quietly.
 */
class EtimsDisabledException extends DigitaxException
{
    public function __construct(public readonly string $businessId, string $message = '')
    {
        parent::__construct($message ?: "eTIMS is not enabled/configured for business {$businessId}");
    }
}
