# uzapoint/digitax-client

DigiTax (KRA eTIMS) v2 API client for Uzapoint Laravel microservices.

It replaces the per-service copies of `App\Services\Etims\DigitaxService`. It
also gives every service a local copy of each business's eTIMS settings, kept
up to date over RabbitMQ (`settings.etims.updated`). Because of that, no
service calls settings-ms over HTTP to find a business's API key.

API docs: https://ke.docs.digitax.tech

## Install

```bash
composer require uzapoint/digitax-client
php artisan migrate   # creates etims_configs
```

Add to `.env` (the same value in every service):

```
ETIMS_CONFIG_KEY=base64:...   # php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
DIGITAX_BASE_URL=https://api.digitax.tech/ke/v2
```

Register the config handler in `config/eventbus.php`:

```php
'queues' => [
    [
        'name' => '<service>.etims_config',
        'exchange' => 'settings.events',
        'routing_keys' => ['settings.etims.updated'],
    ],
],
'handlers' => [
    'settings.etims.updated' => \Uzapoint\Digitax\Sync\HandleEtimsConfigUpdated::class,
],
```

## Use

```php
use Uzapoint\Digitax\DigitaxManager;
use Uzapoint\Digitax\Codes\Module;
use Uzapoint\Digitax\Exceptions\EtimsDisabledException;
use Uzapoint\Digitax\Exceptions\DigitaxApiException;

public function __construct(private DigitaxManager $digitax) {}

if (!$this->digitax->isEnabled($businessId, Module::SALES)) {
    return; // eTIMS off, addon inactive, or sales module not selected
}

try {
    $result = $this->digitax->forBusiness($businessId, Module::SALES)
        ->sales()
        ->createWithItems($payload);         // SaleResult

    $sale->update($result->toColumns());     // etims_url, receipt numbers, status...
} catch (EtimsDisabledException) {
    // turned off between check and call
} catch (DigitaxApiException $e) {
    $e->status; $e->errorCode; $e->userMessage(); $e->isNotEnoughStock();
}
```

`forBusiness()` throws `EtimsDisabledException` or `EtimsModuleDisabledException`
when eTIMS cannot be used for that business. `EtimsModuleDisabledException`
extends `EtimsDisabledException`.

To verify a key before saving it, use `$digitax->withApiKey($key)->business()->etimsInfo()`.

To get an offline QR URL, use `$digitax->offlineUrl($businessId, $saleNumber)`. It
returns `https://receipt.dg.tax/r/<digitax_business_id>/<sale_number>`.

## Endpoints

| Resource | Methods |
|---|---|
| `business()` | `etimsInfo()`, `branches()` |
| `items()` | `create()`, `update()`, `find()`, `stockQuantity()`, `list()`, `all()` |
| `customers()` | `create()`, `update()`, `find()`, `list()`, `all()`, `isValidTin()` |
| `suppliers()` | `create()`, `find()`, `isApproved()`, `list()`, `all()` |
| `imports()` | `list()`, `all()`, `link()` |
| `purchases()` | `list()`, `all()`, `find()`, `linkItems()` |
| `sales()` | `create()`, `createWithItems()`, `find()`, `list()`, `all()`, `findByTraderInvoiceNumber()` |
| `creditNotes()` | `create()`, `createWithBarcode()`, `createExpress()`, `find()`, `list()`, `all()` |
| `reverseInvoices()` | `create()`, `createWithItems()`, `find()`, `list()`, `all()` |
| `reverseCreditNotes()` | `create()`, `createWithBarcode()`, `list()`, `all()` |
| `stock()` | `adjust()`, `adjustRaw()`, `transfer()` |
| `notices()` | `list()`, `all()` |
| `invoiceVerifications()` | `verify()`, `list()`, `all()` |
| `reports()` | `salesSummary()` |

`list()` returns one `CursorPage`. `all()` returns a `LazyCollection` that
follows the `after` cursor across every page.

## Behaviour

- **Requests.** Every request has the `X-API-Key` header, a 5 s connect timeout
  and a 20 s timeout.
  - The client retries GET and PUT on connection errors and on 502, 503 and
    504.
  - It never retries POST automatically. A POST that timed out may already
    have reached DigiTax, so retrying it could create a duplicate or a 409.
- **Payloads.** The client removes null values. It also converts numeric
  strings to numbers, but only for amount and quantity fields. DigiTax's
  strict decoder rejects `"120.00"` for a number field, and it rejects null
  for an optional string.
- **Item create and update.** The client sends both `default_unit_price` and
  `default_price`, because the live validator requires `default_price`.
- **Trader invoice numbers.** Sales, credit notes and reverse invoices check
  `trader_invoice_number` before sending: 1 to 50 characters from
  `[A-Za-z0-9-_.,]`.
- **Callbacks.** When the business config has a public `callback_url`, the
  client adds it to item, sale, credit-note and reverse-invoice creates. To
  leave it out of one request, pass `'callback_url' => false`.
- **Failures.** Failed requests are logged with the request payload, which
  never contains the API key.

## Codes

The `Uzapoint\Digitax\Codes` namespace holds these code tables:

- `PaymentType` (with `fromMethods()`)
- `InvoiceStatus`, `ReceiptType`, `CreditNoteReason`
- `TaxType` (`fromRate()` never guesses a code for 0%)
- `ItemType`, `MovementType`, `StockAction`
- `SyncStatus` (normalises `COMPLETE` and `COMPLETED`)
- `Module`

## Events

`Uzapoint\Digitax\Sync\EtimsEvents` lists every exchange and routing key that
the eTIMS integration uses. Publish and consume through these constants, so
the names stay the same in every service.

settings-ms builds the config message with `EtimsConfigMessage::build()`.
That method encrypts the API key with `ETIMS_CONFIG_KEY`.

## Callbacks

DigiTax does not sign its callbacks. Each business therefore gets a random
token in its callback URL (`Callbacks\CallbackToken`).

To read a callback, use `Callbacks\DigitaxCallback::fromArray($body)`. It
parses `item.sync` and `sale.sync`; `->sale()` returns a `SaleResult`.

## Testing

```php
use Uzapoint\Digitax\Contracts\CredentialResolver;
use Uzapoint\Digitax\Testing\ArrayCredentialResolver;
use Uzapoint\Digitax\Support\DigitaxCredentials;

$this->app->instance(CredentialResolver::class, (new ArrayCredentialResolver())
    ->set(new DigitaxCredentials(businessId: '7', apiKey: 'k', modules: ['sales' => true])));

Http::fake(['https://api.digitax.tech/ke/v2/*' => Http::response([...])]);
```

The package's own tests use in-memory SQLite:

```bash
composer install && vendor/bin/phpunit
```
