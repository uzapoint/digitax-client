<?php

namespace Uzapoint\Digitax\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * DigiTax answered with a non-2xx status. Carries DigiTax's error object
 * ({message, code, metadata}) so callers can show the real reason.
 */
class DigitaxApiException extends DigitaxException
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $requestPayload
     */
    public function __construct(
        public readonly int $status,
        public readonly ?string $errorCode,
        public readonly string $errorMessage,
        public readonly array $metadata,
        public readonly string $method,
        public readonly string $path,
        public readonly array $requestPayload = [],
        public readonly ?string $responseBody = null,
    ) {
        $code = $errorCode ? " {$errorCode}" : '';
        parent::__construct("DigiTax API error ({$status}{$code}): {$errorMessage}", $status);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromResponse(Response $response, string $method, string $path, array $payload = []): self
    {
        $json = $response->json();
        $json = is_array($json) ? $json : [];

        $metadata = $json['metadata'] ?? [];
        // Validation errors arrive as {"fields": {"default_price": ["value is required"]}}.
        if (isset($json['fields']) && is_array($json['fields'])) {
            $metadata['fields'] = $json['fields'];
        }

        return new self(
            status: $response->status(),
            errorCode: isset($json['code']) ? (string) $json['code'] : null,
            errorMessage: (string) ($json['message'] ?? $json['error'] ?? ($response->reason() ?: 'Unknown error')),
            metadata: is_array($metadata) ? $metadata : [],
            method: strtoupper($method),
            path: $path,
            requestPayload: $payload,
            responseBody: $response->body(),
        );
    }

    public function isUnauthorized(): bool
    {
        return $this->status === 401 || $this->status === 403;
    }

    public function isNotFound(): bool
    {
        return $this->status === 404;
    }

    /**
     * 409: usually a trader_invoice_number that was already used.
     */
    public function isConflict(): bool
    {
        return $this->status === 409;
    }

    public function isValidation(): bool
    {
        return $this->status === 400 || $this->status === 422;
    }

    /**
     * DigiTax rejects sales of stockable items it holds too little stock for
     * ("412 not enough stock for item").
     */
    public function isNotEnoughStock(): bool
    {
        return $this->status === 412
            || str_contains(strtolower($this->errorMessage), 'not enough stock');
    }

    public function isServerError(): bool
    {
        return $this->status >= 500;
    }

    public function isRetryable(): bool
    {
        return $this->isServerError() || $this->status === 429 || $this->isNotEnoughStock();
    }

    /**
     * Field-level validation messages, flattened ("default_price: value is required").
     *
     * @return list<string>
     */
    public function fieldErrors(): array
    {
        $fields = $this->metadata['fields'] ?? [];
        if (!is_array($fields)) {
            return [];
        }

        $errors = [];
        foreach ($fields as $field => $messages) {
            foreach ((array) $messages as $message) {
                $errors[] = "{$field}: {$message}";
            }
        }

        return $errors;
    }

    /**
     * Short message safe to show to a user.
     */
    public function userMessage(): string
    {
        $fields = $this->fieldErrors();

        return $fields === []
            ? $this->errorMessage
            : $this->errorMessage . ' (' . implode('; ', $fields) . ')';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'code' => $this->errorCode,
            'message' => $this->errorMessage,
            'metadata' => $this->metadata,
            'method' => $this->method,
            'path' => $this->path,
        ];
    }
}
