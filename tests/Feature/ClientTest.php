<?php

namespace Uzapoint\Digitax\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Uzapoint\Digitax\Codes\Module;
use Uzapoint\Digitax\Codes\MovementType;
use Uzapoint\Digitax\Exceptions\DigitaxApiException;
use Uzapoint\Digitax\Exceptions\DigitaxConnectionException;
use Uzapoint\Digitax\Exceptions\EtimsDisabledException;
use Uzapoint\Digitax\Exceptions\EtimsModuleDisabledException;
use Uzapoint\Digitax\Tests\TestCase;

class ClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useArrayResolver()->set($this->credentials());
    }

    public function test_disabled_business_throws(): void
    {
        $this->assertFalse($this->manager()->isEnabled(99));
        $this->expectException(EtimsDisabledException::class);
        $this->manager()->forBusiness(99);
    }

    public function test_module_gating(): void
    {
        $this->assertTrue($this->manager()->isEnabled(7, Module::PRODUCTS));
        $this->assertTrue($this->manager()->isEnabled(7, Module::SALES));
        $this->assertFalse($this->manager()->isEnabled(7, Module::PURCHASES));

        $this->expectException(EtimsModuleDisabledException::class);
        $this->manager()->forBusiness(7, Module::PURCHASES);
    }

    public function test_item_create_sends_key_both_prices_and_callback(): void
    {
        Http::fake([self::BASE . '/items' => Http::response(['id' => 'item_1', 'status' => 'PENDING'], 201)]);

        $item = $this->manager()->forBusiness(7)->items()->create([
            'item_name' => 'Soap',
            'default_unit_price' => '120.00',
            'tax_type_code' => 'B',
            'item_bar_code' => null,
        ]);

        $this->assertSame('item_1', $item['id']);
        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request->url() === self::BASE . '/items'
                && $request->hasHeader('X-API-Key', 'test-key')
                && $request['default_price'] === 120.0
                && $request['default_unit_price'] === 120.0
                && $request['callback_url'] === 'https://api.uzapoint.com/v1/callbacks/digitax/tok'
                && !array_key_exists('item_bar_code', $request->data());
        });
    }

    public function test_callback_can_be_opted_out(): void
    {
        Http::fake([self::BASE . '/sales-with-items' => Http::response(['id' => 'sale_1', 'status' => 'COMPLETED'], 201)]);

        $this->manager()->forBusiness(7, Module::SALES)->sales()->createWithItems([
            'trader_invoice_number' => 'SAL-000001',
            'callback_url' => false,
            'items' => [],
        ]);

        Http::assertSent(fn (Request $r) => !array_key_exists('callback_url', $r->data()));
    }

    public function test_sale_rejects_invalid_trader_invoice_number_before_calling(): void
    {
        Http::fake();
        $this->expectException(\InvalidArgumentException::class);

        try {
            $this->manager()->forBusiness(7)->sales()->createWithItems(['trader_invoice_number' => 'SAL 1', 'items' => []]);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_api_error_is_typed(): void
    {
        Http::fake([self::BASE . '/items' => Http::response([
            'message' => 'invalid request data',
            'code' => 'VALIDATION',
            'fields' => ['default_price' => ['value is required']],
        ], 400)]);

        try {
            $this->manager()->forBusiness(7)->items()->create(['item_name' => 'x']);
            $this->fail('Expected DigitaxApiException');
        } catch (DigitaxApiException $e) {
            $this->assertSame(400, $e->status);
            $this->assertSame('VALIDATION', $e->errorCode);
            $this->assertTrue($e->isValidation());
            $this->assertSame(['default_price: value is required'], $e->fieldErrors());
            $this->assertSame('invalid request data (default_price: value is required)', $e->userMessage());
            $this->assertSame('x', $e->requestPayload['item_name']);
        }
    }

    public function test_not_enough_stock_detection(): void
    {
        Http::fake([self::BASE . '/sales-with-items' => Http::response(['message' => 'not enough stock for item', 'code' => 'STOCK'], 412)]);

        try {
            $this->manager()->forBusiness(7)->sales()->createWithItems(['trader_invoice_number' => 'SAL-1', 'items' => []]);
            $this->fail('Expected DigitaxApiException');
        } catch (DigitaxApiException $e) {
            $this->assertTrue($e->isNotEnoughStock());
            $this->assertTrue($e->isRetryable());
        }
    }

    public function test_get_retries_on_503(): void
    {
        Http::fakeSequence(self::BASE . '/items/item_1')
            ->push(['message' => 'busy'], 503)
            ->push(['id' => 'item_1', 'stock_quantity' => 7, 'is_stock_item' => true], 200);

        $this->assertSame(7.0, $this->manager()->forBusiness(7)->items()->stockQuantity('item_1'));
        Http::assertSentCount(2);
    }

    public function test_post_is_never_retried(): void
    {
        Http::fake([self::BASE . '/credit-notes' => Http::sequence()
            ->push(['message' => 'busy', 'code' => 'X'], 503)
            ->push(['id' => 'should-not-happen'], 201)]);

        try {
            $this->manager()->forBusiness(7)->creditNotes()->create(['trader_invoice_number' => 'SAL-1-V1', 'items' => []]);
            $this->fail('Expected DigitaxApiException');
        } catch (DigitaxApiException $e) {
            $this->assertSame(503, $e->status);
        }

        Http::assertSentCount(1);
    }

    public function test_connection_failure(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out'));

        $this->expectException(DigitaxConnectionException::class);
        $this->manager()->forBusiness(7)->business()->etimsInfo();
    }

    public function test_stock_adjust_payload(): void
    {
        Http::fake([self::BASE . '/stock/adjust' => Http::response(['id' => 'item_1', 'stock_quantity' => 12])]);

        $this->manager()->forBusiness(7)->stock()->adjust('item_1', '-5', MovementType::OUTGOING_DISCARDING);

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && $r->data() === ['item_id' => 'item_1', 'quantity' => 5.0, 'action' => 'DEDUCT', 'movement_type' => '15']);
    }

    public function test_cursor_walks_every_page(): void
    {
        Http::fake([
            self::BASE . '/purchases*' => Http::sequence()
                ->push(['data' => [['id' => 'p1'], ['id' => 'p2']], 'pagination' => ['next' => 'p2']])
                ->push(['data' => [['id' => 'p3']], 'pagination' => ['next' => null]]),
        ]);

        $ids = $this->manager()->forBusiness(7)->purchases()->all()->pluck('id')->all();

        $this->assertSame(['p1', 'p2', 'p3'], $ids);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'after=p2'));
    }

    public function test_link_items_accepts_map(): void
    {
        Http::fake([self::BASE . '/purchases/purchase_1/link-items' => Http::response(['id' => 'purchase_1'])]);

        $this->manager()->forBusiness(7)->purchases()->linkItems('purchase_1', ['pi_1' => 'item_1']);

        Http::assertSent(fn (Request $r) => $r['items'] === [['purchase_item_id' => 'pi_1', 'item_id' => 'item_1']]);
    }

    public function test_reverse_invoice_items_get_required_discount_fields(): void
    {
        Http::fake([self::BASE . '/reverse-invoices-with-items' => Http::response(['id' => 'sale_r1', 'etims_url' => 'https://k'], 201)]);

        $result = $this->manager()->forBusiness(7)->reverseInvoices()->createWithItems([
            'supplier_id' => 'supplier_1',
            'sale_date' => '2026-11-05',
            'trader_invoice_number' => 'GRN-0001',
            'items' => [['item_name' => 'Maize', 'quantity' => 10, 'unit_price' => 50, 'total_amount' => 500]],
        ]);

        $this->assertSame('https://k', $result->etimsUrl());
        Http::assertSent(fn (Request $r) => $r['items'][0]['discount_rate'] === 0.0 && $r['items'][0]['discount_amount'] === 0.0);
    }

    public function test_invoice_verification_amount_is_integer(): void
    {
        Http::fake([self::BASE . '/invoice-verifications' => Http::response(['status' => 'success'], 201)]);

        $this->manager()->forBusiness(7)->invoiceVerifications()->verify('INV-9', 1234.6, 'P051234567X');

        Http::assertSent(fn (Request $r) => $r['amount'] === 1235 && !array_key_exists('reference_number', $r->data()));
    }

    public function test_offline_url_and_ad_hoc_key(): void
    {
        $this->assertSame('https://receipt.dg.tax/r/business_01ABC/SAL-000009', $this->manager()->offlineUrl(7, 'SAL-000009'));
        $this->assertNull($this->manager()->offlineUrl(99, 'SAL-000009'));

        Http::fake([self::BASE . '/etims-info' => Http::response(['business_id' => 'business_X', 'tax_pin' => 'P0'])]);
        $info = $this->manager()->withApiKey('candidate-key')->business()->etimsInfo();

        $this->assertSame('business_X', $info['business_id']);
        Http::assertSent(fn (Request $r) => $r->hasHeader('X-API-Key', 'candidate-key'));
    }

    public function test_environment_base_url_override(): void
    {
        config(['digitax.base_urls.live' => 'https://live.example.test/ke/v2']);
        $this->resolver->set($this->credentials(['environment' => 'live']));
        Http::fake(['https://live.example.test/*' => Http::response(['ok' => true])]);

        $this->manager()->forBusiness(7)->business()->etimsInfo();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://live.example.test/ke/v2/etims-info');
    }
}
