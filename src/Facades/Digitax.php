<?php

namespace Uzapoint\Digitax\Facades;

use Illuminate\Support\Facades\Facade;
use Uzapoint\Digitax\DigitaxClient;
use Uzapoint\Digitax\DigitaxManager;
use Uzapoint\Digitax\Support\DigitaxCredentials;

/**
 * @method static bool isEnabled(int|string $businessId, ?string $module = null)
 * @method static DigitaxClient forBusiness(int|string $businessId, ?string $module = null)
 * @method static DigitaxClient withCredentials(DigitaxCredentials $credentials)
 * @method static DigitaxClient withApiKey(string $apiKey, string $environment = 'test')
 * @method static DigitaxCredentials|null credentials(int|string $businessId)
 * @method static string|null offlineUrl(int|string $businessId, string $traderInvoiceNumber)
 *
 * @see DigitaxManager
 */
class Digitax extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DigitaxManager::class;
    }
}
