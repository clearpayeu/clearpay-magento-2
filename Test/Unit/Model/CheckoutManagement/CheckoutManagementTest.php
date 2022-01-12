<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Test\Unit\Model\CheckoutManagement;

use Clearpay\Clearpay\Api\Data\CheckoutInterface;

class CheckoutManagementTest extends \PHPUnit\Framework\TestCase
{
    private $checkoutManagement;

    /** @var \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $maskedQuiteIdToQuoteIdMock;

    /** @var \Magento\Quote\Api\CartRepositoryInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $cartRepositoryMock;

    /** @var \Clearpay\Clearpay\Api\Data\RedirectPathInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $redirectPathStub;

    /** @var \Magento\Quote\Model\Quote|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteStub;

    /** @var \Magento\Quote\Model\Quote\Payment|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMock;

    protected function setUp(): void
    {
        $this->cartRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->maskedQuiteIdToQuoteIdMock = $this->createMock(
            \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface::class
        );
        $checkoutFactoryStub = $this->createMock(\Clearpay\Clearpay\Api\Data\CheckoutInterfaceFactory::class);

        $this->checkoutManagement = new \Clearpay\Clearpay\Model\CheckoutManagement\CheckoutManagement(
            $this->createMock(\Magento\Payment\Gateway\CommandInterface::class),
            $this->createMock(\Magento\Payment\Gateway\CommandInterface::class),
            $this->cartRepositoryMock,
            $this->maskedQuiteIdToQuoteIdMock,
            $checkoutFactoryStub
        );

        $checkoutFactoryStub->method('create')
            ->willReturn(new \Clearpay\Clearpay\Model\Checkout());

        $this->redirectPathStub = $this->createMock(\Clearpay\Clearpay\Api\Data\RedirectPathInterface::class);

        $this->quoteStub = $this->createMock(\Magento\Quote\Model\Quote::class);

        $this->quoteStub->method('reserveOrderId')
            ->willReturnSelf();

        $this->paymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);

        $this->quoteStub->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->cartRepositoryMock->expects($this->any())
            ->method('save')
            ->willReturn($this->quoteStub);

        $this->cartRepositoryMock
            ->method('getActive')
            ->willReturn($this->quoteStub);
    }

    public function testQuoteMaskedIdConvertedToQuoteId()
    {
        $maskedQuiteId = 'asdad123asdqwe123asd';
        $quoteId = 122;

        $this->paymentMock->method('getAdditionalInformation')
            ->willReturn('fake-additional-information');

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->willReturn($quoteId);

        $this->cartRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with($quoteId)
            ->willReturn($this->quoteStub);

        $this->checkoutManagement->create($maskedQuiteId, $this->redirectPathStub);
    }

    public function testQuoteIdIsNotConverted()
    {
        $quoteId = '322';

        $this->paymentMock->method('getAdditionalInformation')
            ->willReturn('fake-additional-information');

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with($quoteId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with($quoteId)
            ->willReturn($this->quoteStub);

        $this->checkoutManagement->create($quoteId, $this->redirectPathStub);
    }

    public function testCreateCheckout()
    {
        $quoteId = '333';
        $clearpayToken = 'asdasdasdasdsadasd';
        $clearpayAuthTokenExpires = '2021-01-01 00:00:00';
        $clearpayRedirectCheckoutUrl = 'https://test-clearpay.com/redirect?token=123';

        $this->paymentMock->method('getAdditionalInformation')
            ->will(
                $this->returnValueMap([
                    [CheckoutInterface::CLEARPAY_TOKEN, $clearpayToken],
                    [CheckoutInterface::CLEARPAY_AUTH_TOKEN_EXPIRES, $clearpayAuthTokenExpires],
                    [CheckoutInterface::CLEARPAY_REDIRECT_CHECKOUT_URL, $clearpayRedirectCheckoutUrl],
                ])
            );

        $checkout = $this->checkoutManagement->create($quoteId, $this->redirectPathStub);

        static::assertSame($clearpayToken, $checkout->getClearpayToken());
        static::assertSame($clearpayAuthTokenExpires, $checkout->getClearpayAuthTokenExpires());
        static::assertSame($clearpayRedirectCheckoutUrl, $checkout->getClearpayRedirectCheckoutUrl());
    }

    public function testCreateExpressCheckout()
    {
        $quoteId = '333';
        $clearpayToken = 'asdasdasdasdsadasd';
        $clearpayAuthTokenExpires = '2021-01-01 00:00:00';
        $clearpayRedirectCheckoutUrl = 'https://test-clearpay.com/redirect?token=123';

        $testPopupOriginUrl = 'https://test-clearpay.com/checkout/cart';

        $this->paymentMock->method('getAdditionalInformation')
            ->will(
                $this->returnValueMap([
                    [CheckoutInterface::CLEARPAY_TOKEN, $clearpayToken],
                    [CheckoutInterface::CLEARPAY_AUTH_TOKEN_EXPIRES, $clearpayAuthTokenExpires],
                    [CheckoutInterface::CLEARPAY_REDIRECT_CHECKOUT_URL, $clearpayRedirectCheckoutUrl],
                ])
            );

        $checkout = $this->checkoutManagement->createExpress($quoteId, $testPopupOriginUrl);

        static::assertSame($clearpayToken, $checkout->getClearpayToken());
        static::assertSame($clearpayAuthTokenExpires, $checkout->getClearpayAuthTokenExpires());
        static::assertSame($clearpayRedirectCheckoutUrl, $checkout->getClearpayRedirectCheckoutUrl());
    }
}
