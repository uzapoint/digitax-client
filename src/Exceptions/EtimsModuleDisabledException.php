<?php

namespace Uzapoint\Digitax\Exceptions;

/**
 * eTIMS is on for the business, but this sync module (sales, purchases) is
 * not selected. Extends EtimsDisabledException so existing "skip quietly"
 * catch blocks keep working.
 */
class EtimsModuleDisabledException extends EtimsDisabledException
{
    public function __construct(string $businessId, public readonly string $module)
    {
        parent::__construct($businessId, "eTIMS module '{$module}' is not enabled for business {$businessId}");
    }
}
