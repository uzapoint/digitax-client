<?php

namespace Uzapoint\Digitax\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Uzapoint\Digitax\Codes\CreditNoteReason;
use Uzapoint\Digitax\Codes\Module;
use Uzapoint\Digitax\Codes\MovementType;
use Uzapoint\Digitax\Codes\PaymentType;
use Uzapoint\Digitax\Codes\StockAction;
use Uzapoint\Digitax\Codes\SyncStatus;
use Uzapoint\Digitax\Codes\TaxType;

class CodesTest extends TestCase
{
    public function test_payment_type_maps_methods(): void
    {
        $this->assertSame('01', PaymentType::fromMethod('Cash'));
        $this->assertSame('06', PaymentType::fromMethod('mpesa'));
        $this->assertSame('05', PaymentType::fromMethod('credit_card'));
        $this->assertSame('07', PaymentType::fromMethod('voucher'));
    }

    public function test_payment_type_for_several_methods(): void
    {
        $this->assertSame('02', PaymentType::fromMethods(['cash'], unpaid: true));
        $this->assertSame('01', PaymentType::fromMethods([]));
        $this->assertSame('06', PaymentType::fromMethods(['mpesa', 'mobile_money']));
        $this->assertSame('03', PaymentType::fromMethods(['cash', 'credit']));
        $this->assertSame('07', PaymentType::fromMethods(['cash', 'mpesa']));
    }

    public function test_tax_type_from_rate_never_guesses_zero(): void
    {
        $this->assertSame('B', TaxType::fromRate(16));
        $this->assertSame('B', TaxType::fromRate('16.00'));
        $this->assertSame('E', TaxType::fromRate(8));
        $this->assertNull(TaxType::fromRate(0));
        $this->assertNull(TaxType::fromRate(null));
        $this->assertNull(TaxType::fromRate(12));
    }

    public function test_sync_status_normalisation(): void
    {
        $this->assertSame(SyncStatus::COMPLETE, SyncStatus::normalize('COMPLETE'));
        $this->assertSame(SyncStatus::COMPLETE, SyncStatus::normalize('COMPLETED'));
        $this->assertSame(SyncStatus::FAILED, SyncStatus::normalize('failed'));
        $this->assertSame(SyncStatus::PENDING, SyncStatus::normalize('SUBMITTED'));
        $this->assertSame(SyncStatus::COMPLETE, SyncStatus::normalize(null));
    }

    public function test_movement_type_direction(): void
    {
        $this->assertSame(StockAction::ADD, MovementType::action(MovementType::INCOMING_ADJUSTMENT));
        $this->assertSame(StockAction::DEDUCT, MovementType::action(MovementType::OUTGOING_DISCARDING));
    }

    public function test_modules_force_products_and_stocks(): void
    {
        $this->assertSame(
            ['products' => true, 'stocks' => true, 'sales' => false, 'purchases' => true],
            Module::normalize(['products' => false, 'stocks' => false, 'purchases' => '1'])
        );
    }

    public function test_credit_note_reason_default(): void
    {
        $this->assertSame('06', CreditNoteReason::orDefault(null));
        $this->assertSame('03', CreditNoteReason::orDefault('03'));
        $this->assertSame('06', CreditNoteReason::orDefault('99'));
    }
}
