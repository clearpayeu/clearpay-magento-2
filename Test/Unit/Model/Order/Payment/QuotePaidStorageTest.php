<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Test\Unit\Model\Order\Payment;

class QuotePaidStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testStorageSetGet()
    {
        $testQuoteId = 1;
        $testPaymentId = 333;
        $testClearpayOrderPaymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);

        $testClearpayOrderPaymentMock->expects($this->any())
            ->method('getId')
            ->willReturn($testPaymentId);

        $quotePaidStorage = new \Clearpay\Clearpay\Model\Order\Payment\QuotePaidStorage();
        $quotePaidStorage->setClearpayPaymentForQuote($testQuoteId, $testClearpayOrderPaymentMock);
        static::assertSame(
            $testClearpayOrderPaymentMock->getId(),
            $quotePaidStorage->getClearpayPaymentIfQuoteIsPaid($testQuoteId)->getId()
        );
    }

    public function testEmptyPayment()
    {
        $testUnexistedQuoteId = 99;

        $quotePaidStorage = new \Clearpay\Clearpay\Model\Order\Payment\QuotePaidStorage();
        static::assertSame($quotePaidStorage->getClearpayPaymentIfQuoteIsPaid($testUnexistedQuoteId), null);
    }
}
