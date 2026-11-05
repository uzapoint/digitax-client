<?php

namespace Uzapoint\Digitax;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Uzapoint\Digitax\Exceptions\DigitaxApiException;
use Uzapoint\Digitax\Exceptions\DigitaxConnectionException;
use Uzapoint\Digitax\Resources\Business;
use Uzapoint\Digitax\Resources\CreditNotes;
use Uzapoint\Digitax\Resources\Customers;
use Uzapoint\Digitax\Resources\Imports;
use Uzapoint\Digitax\Resources\InvoiceVerifications;
use Uzapoint\Digitax\Resources\Items;
use Uzapoint\Digitax\Resources\Notices;
use Uzapoint\Digitax\Resources\Purchases;
use Uzapoint\Digitax\Resources\Reports;
use Uzapoint\Digitax\Resources\ReverseCreditNotes;
use Uzapoint\Digitax\Resources\ReverseInvoices;
use Uzapoint\Digitax\Resources\Sales;
use Uzapoint\Digitax\Resources\Stock;
use Uzapoint\Digitax\Resources\Suppliers;
use Uzapoint\Digitax\Support\DigitaxCredentials;
use Uzapoint\Digitax\Support\OfflineUrl;
use Uzapoint\Digitax\Support\Payload;

/**
 * DigiTax v2 client bound to one business's credentials.
 *
 * Get one from DigitaxManager::forBusiness() (respects ETIMS_ENABLED and
 * sync modules) or DigitaxManager::withCredentials() (ad-hoc, e.g. to
 * verify a key before saving it).
 *
 * Docs: https://ke.docs.digitax.tech
 */
class DigitaxClient
{
    private const IDEMPOTENT = ['GET', 'PUT'];
    private const RETRY_STATUSES = [502, 503, 504];

    /** @var array<string, object> */
    private array $resources = [];

    /**
     * @param  array<string, mixed>  $config  the digitax config array
     */
    public function __construct(
        protected HttpFactory $http,
        public readonly DigitaxCredentials $credentials,
        protected array $config = [],
    ) {
    }

    // ---------------------------------------------------------------------
    // Resources
    // ---------------------------------------------------------------------

    public function business(): Business
    {
        return $this->resource(Business::class);
    }

    public function items(): Items
    {
        return $this->resource(Items::class);
    }

    public function customers(): Customers
    {
        return $this->resource(Customers::class);
    }

    public function suppliers(): Suppliers
    {
        return $this->resource(Suppliers::class);
    }

    public function imports(): Imports
    {
        return $this->resource(Imports::class);
    }

    public function purchases(): Purchases
    {
        return $this->resource(Purchases::class);
    }

    public function sales(): Sales
    {
        return $this->resource(Sales::class);
    }

    public function creditNotes(): CreditNotes
    {
        return $this->resource(CreditNotes::class);
    }

    public function reverseInvoices(): ReverseInvoices
    {
        return $this->resource(ReverseInvoices::class);
    }

    public function reverseCreditNotes(): ReverseCreditNotes
    {
        return $this->resource(ReverseCreditNotes::class);
    }

    public function stock(): Stock
    {
        return $this->resource(Stock::class);
    }

    public function notices(): Notices
    {
        return $this->resource(Notices::class);
    }

    public function invoiceVerifications(): InvoiceVerifications
    {
        return $this->resource(InvoiceVerifications::class);
    }

    public function reports(): Reports
    {
        return $this->resource(Reports::class);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    public function businessId(): string
    {
        return $this->credentials->businessId;
    }

    public function callbackUrl(): ?string
    {
        return $this->credentials->callbackUrl;
    }

    public function offlineUrl(string $traderInvoiceNumber): ?string
    {
        return OfflineUrl::make(
            $this->credentials->digitaxBusinessId,
            $traderInvoiceNumber,
            $this->config['offline_url'] ?? null,
        );
    }

    /**
     * Add the business callback URL to a create payload unless the caller
     * set one (or explicitly set it to false to opt out).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function withCallback(array $payload): array
    {
        if (array_key_exists('callback_url', $payload)) {
            if ($payload['callback_url'] === false) {
                unset($payload['callback_url']);
            }

            return $payload;
        }

        if ($url = $this->callbackUrl()) {
            $payload['callback_url'] = $url;
        }

        return $payload;
    }

    public function baseUrl(): string
    {
        $env = $this->credentials->environment;

        return rtrim(
            $this->credentials->baseUrl
                ?: ($this->config['base_urls'][$env] ?? null)
                ?: ($this->config['base_url'] ?? 'https://api.digitax.tech/ke/v2'),
            '/'
        );
    }

    // ---------------------------------------------------------------------
    // Transport
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $query
     * @return array<array-key, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public function post(string $path, array $payload = []): array
    {
        return $this->send('POST', $path, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public function put(string $path, array $payload = []): array
    {
        return $this->send('PUT', $path, $payload);
    }

    /**
     * @param  array<string, mixed>  $data  query for GET, JSON body otherwise
     * @return array<array-key, mixed>
     */
    public function send(string $method, string $path, array $data = []): array
    {
        $method = strtoupper($method);
        $path = '/' . ltrim($path, '/');
        $data = Payload::clean($data);
        $url = $this->baseUrl() . $path;

        $attempts = in_array($method, self::IDEMPOTENT, true)
            ? 1 + max(0, (int) ($this->config['retries'] ?? 2))
            : 1;

        $attempt = 0;
        while (true) {
            $attempt++;

            try {
                $response = $this->dispatch($method, $url, $data);
            } catch (ConnectionException $e) {
                if ($attempt < $attempts) {
                    $this->pause($attempt);
                    continue;
                }

                $this->logFailure($method, $path, $data, null, $e->getMessage());
                throw new DigitaxConnectionException($method, $path, $e->getMessage(), $e);
            }

            if ($response->successful()) {
                $json = $response->json();

                return is_array($json) ? $json : [];
            }

            if ($attempt < $attempts && in_array($response->status(), self::RETRY_STATUSES, true)) {
                $this->pause($attempt);
                continue;
            }

            $this->logFailure($method, $path, $data, $response);
            throw DigitaxApiException::fromResponse($response, $method, $path, $data);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function dispatch(string $method, string $url, array $data): Response
    {
        $request = $this->request();

        return match ($method) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PUT' => $request->put($url, $data),
            'DELETE' => $request->delete($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method {$method}"),
        };
    }

    protected function request(): PendingRequest
    {
        return $this->http
            ->withHeaders(['X-API-Key' => $this->credentials->apiKey])
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) ($this->config['connect_timeout'] ?? 5))
            ->timeout((int) ($this->config['timeout'] ?? 20));
    }

    private function pause(int $attempt): void
    {
        $ms = (int) ($this->config['retry_sleep_ms'] ?? 500) * $attempt;
        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logFailure(string $method, string $path, array $payload, ?Response $response, ?string $error = null): void
    {
        $context = [
            'business_id' => $this->credentials->businessId,
            'environment' => $this->credentials->environment,
            'method' => $method,
            'path' => $path,
            'status' => $response?->status(),
            'response' => $response?->body(),
            'error' => $error,
        ];

        if ($this->config['log_failed_payloads'] ?? true) {
            $context['request_payload'] = $payload;
        }

        $channel = $this->config['log_channel'] ?? null;
        ($channel ? Log::channel($channel) : Log::getFacadeRoot())
            ->error("DigiTax {$method} {$path} failed", $context);
    }

    /**
     * @template T of object
     * @param  class-string<T>  $class
     * @return T
     */
    private function resource(string $class): object
    {
        return $this->resources[$class] ??= new $class($this);
    }
}
