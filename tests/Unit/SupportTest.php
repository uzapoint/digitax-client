<?php

namespace Uzapoint\Digitax\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Uzapoint\Digitax\Callbacks\CallbackToken;
use Uzapoint\Digitax\Callbacks\DigitaxCallback;
use Uzapoint\Digitax\Support\CursorPage;
use Uzapoint\Digitax\Support\OfflineUrl;
use Uzapoint\Digitax\Support\Payload;
use Uzapoint\Digitax\Support\SaleResult;
use Uzapoint\Digitax\Support\TraderInvoiceNumber;

class SupportTest extends TestCase
{
    public function test_trader_invoice_number_rules(): void
    {
        $this->assertTrue(TraderInvoiceNumber::isValid('SAL-000123'));
        $this->assertTrue(TraderInvoiceNumber::isValid('A_b.c,1'));
        $this->assertFalse(TraderInvoiceNumber::isValid('SAL 1'));
        $this->assertFalse(TraderInvoiceNumber::isValid('SAL/1'));
        $this->assertFalse(TraderInvoiceNumber::isValid(str_repeat('a', 51)));
        $this->assertFalse(TraderInvoiceNumber::isValid(''));

        $this->assertSame('INV-2026-01', TraderInvoiceNumber::sanitize(' INV / 2026 #01 '));
        $this->assertSame(50, strlen(TraderInvoiceNumber::sanitize(str_repeat('x', 80))));
        $this->assertSame('SAL-000123-V2', TraderInvoiceNumber::forVoid('SAL-000123', 2));
    }

    public function test_assert_rejects_invalid_numbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TraderInvoiceNumber::assert('SAL 000123');
    }

    public function test_offline_url(): void
    {
        $this->assertSame(
            'https://receipt.dg.tax/r/business_01ABC/SAL-000123',
            OfflineUrl::make('business_01ABC', 'SAL-000123', 'https://receipt.dg.tax/r/')
        );
        $this->assertNull(OfflineUrl::make(null, 'SAL-000123', 'https://receipt.dg.tax/r'));
        $this->assertNull(OfflineUrl::make('business_01ABC', 'bad number', 'https://receipt.dg.tax/r'));
    }

    public function test_payload_clean_drops_nulls_and_casts_only_numeric_keys(): void
    {
        $clean = Payload::clean([
            'item_bar_code' => null,
            'payment_type_code' => '02',
            'item_class_code' => '99010000',
            'default_price' => '120.00',
            'items' => [['quantity' => '2', 'unit_price' => '50.5', 'item_description' => null]],
        ]);

        $this->assertSame([
            'payment_type_code' => '02',
            'item_class_code' => '99010000',
            'default_price' => 120.0,
            'items' => [['quantity' => 2.0, 'unit_price' => 50.5]],
        ], $clean);
    }

    public function test_sale_result_columns(): void
    {
        $result = SaleResult::from([
            'id' => 'sale_01',
            'status' => 'COMPLETED',
            'receipt_number' => 44,
            'invoice_number' => 12,
            'serial_number' => 'KRACU01',
            'receipt_signature' => 'SIG',
            'etims_url' => 'https://etims.kra.go.ke/x',
            'receipt_type_code' => 'R',
        ]);

        $this->assertTrue($result->isComplete());
        $this->assertTrue($result->isCreditNote());
        $this->assertSame('https://etims.kra.go.ke/x', $result->qrUrl());
        $this->assertSame('44', $result->toColumns()['etims_receipt_number']);
        $this->assertSame('complete', $result->toColumns()['etims_sync_status']);
    }

    public function test_cursor_page(): void
    {
        $page = CursorPage::from(['data' => [['id' => 'a']], 'pagination' => ['next' => 'x', 'page_size' => 20]]);
        $this->assertTrue($page->hasMore());
        $this->assertSame('x', $page->next);

        $last = CursorPage::from(['data' => [], 'pagination' => ['next' => '']]);
        $this->assertFalse($last->hasMore());
    }

    public function test_callback_parsing(): void
    {
        $cb = DigitaxCallback::fromArray([
            'event' => 'sale.sync',
            'data' => ['id' => 'sale_1', 'queue_status' => 'COMPLETED', 'etims_url' => 'https://k'],
        ]);

        $this->assertTrue($cb->isSaleSync());
        $this->assertSame('sale_1', $cb->id());
        $this->assertSame('complete', $cb->status());
        $this->assertTrue($cb->sale()->isComplete());

        $this->expectException(InvalidArgumentException::class);
        DigitaxCallback::fromArray(['event' => 'other', 'data' => []]);
    }

    public function test_callback_url_must_be_public(): void
    {
        $this->assertTrue(CallbackToken::isPublicUrl('https://api.uzapoint.com/v1/callbacks/digitax/abc'));
        $this->assertFalse(CallbackToken::isPublicUrl('http://localhost:8000/x'));
        $this->assertFalse(CallbackToken::isPublicUrl('http://titan_gateway/x'));
        $this->assertFalse(CallbackToken::isPublicUrl('http://192.168.1.4/x'));
        $this->assertFalse(CallbackToken::isPublicUrl('https://u:p@api.uzapoint.com/x'));
        $this->assertSame(48, strlen(CallbackToken::generate()));
    }
}
