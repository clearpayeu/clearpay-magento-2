<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Test\Unit\Model\CheckoutManagement;

use Clearpay\Clearpay\Api\Data\CheckoutInterface;
use Magento\Authorization\Model\UserContextInterface;

class CheckoutManagementTest extends \PHPUnit\Framework\TestCase
{
    private const CART_OWNER_ID = 7;
    private const REFUSAL_MESSAGE_PATTERN = '/^No such entity with cartId = 322$/';

    private \Clearpay\Clearpay\Model\CheckoutManagement\CheckoutManagement $checkoutManagement;

    /** @var \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $maskedQuiteIdToQuoteIdMock;

    /** @var \Magento\Quote\Api\CartRepositoryInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $cartRepositoryMock;

    /** @var \Magento\Authorization\Model\UserContextInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $userContextMock;

    /** @var \Clearpay\Clearpay\Api\Data\RedirectPathInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $redirectPathStub;

    /** @var \Magento\Quote\Model\Quote|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteStub;

    /** @var \Magento\Quote\Model\Quote\Payment|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMock;

    /** Caller identity the UserContextInterface mock reports; mutated per test. */
    private ?int $callerUserId = self::CART_OWNER_ID;

    /** Caller type the UserContextInterface mock reports; mutated per test. */
    private ?int $callerUserType = UserContextInterface::USER_TYPE_CUSTOMER;

    protected function setUp(): void
    {
        $this->cartRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->maskedQuiteIdToQuoteIdMock = $this->createMock(
            \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface::class
        );
        $checkoutFactoryStub = $this->createMock(\Clearpay\Clearpay\Api\Data\CheckoutInterfaceFactory::class);

        $this->userContextMock = $this->createMock(UserContextInterface::class);
        $this->userContextMock->method('getUserId')
            ->willReturnCallback(fn () => $this->callerUserId);
        $this->userContextMock->method('getUserType')
            ->willReturnCallback(fn () => $this->callerUserType);

        $this->checkoutManagement = new \Clearpay\Clearpay\Model\CheckoutManagement\CheckoutManagement(
            $this->createMock(\Magento\Payment\Gateway\CommandInterface::class),
            $this->createMock(\Magento\Payment\Gateway\CommandInterface::class),
            $this->cartRepositoryMock,
            $this->maskedQuiteIdToQuoteIdMock,
            $checkoutFactoryStub,
            $this->userContextMock
        );

        $checkoutFactoryStub->method('create')
            ->willReturn(new \Clearpay\Clearpay\Model\Checkout());

        $this->redirectPathStub = $this->createMock(\Clearpay\Clearpay\Api\Data\RedirectPathInterface::class);

        $this->quoteStub = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerId'])
            ->onlyMethods(['reserveOrderId', 'getPayment'])
            ->getMock();

        $this->quoteStub->method('reserveOrderId')
            ->willReturnSelf();

        $this->quoteStub->method('getCustomerId')
            ->willReturn(self::CART_OWNER_ID);

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

    /**
     * A masked cart id is an unguessable capability, so it is accepted without a customer session.
     */
    public function testQuoteMaskedIdIsAcceptedForGuestCaller()
    {
        $this->callerUserType = UserContextInterface::USER_TYPE_GUEST;
        $this->callerUserId = 0;

        $this->paymentMock->method('getAdditionalInformation')
            ->willReturn('fake-additional-information');

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->willReturn(122);

        $this->cartRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with(122)
            ->willReturn($this->quoteStub);

        $this->checkoutManagement->create('asdad123asdqwe123asd', $this->redirectPathStub);
    }

    public function testNumericQuoteIdIsAcceptedForOwningCustomer()
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
            ->with((int)$quoteId)
            ->willReturn($this->quoteStub);

        $this->checkoutManagement->create($quoteId, $this->redirectPathStub);
    }

    public function testNumericQuoteIdIsRefusedForAnonymousCaller()
    {
        $this->callerUserType = UserContextInterface::USER_TYPE_GUEST;
        $this->callerUserId = 0;

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with('322')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->never())
            ->method('getActive');

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessageMatches(self::REFUSAL_MESSAGE_PATTERN);

        $this->checkoutManagement->create('322', $this->redirectPathStub);
    }

    public function testNumericQuoteIdIsRefusedWhenNoUserContextResolves()
    {
        $this->callerUserType = null;
        $this->callerUserId = null;

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with('322')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->never())
            ->method('getActive');

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessageMatches(self::REFUSAL_MESSAGE_PATTERN);

        $this->checkoutManagement->create('322', $this->redirectPathStub);
    }

    public function testNumericQuoteIdIsRefusedForIntegrationCaller()
    {
        $this->callerUserType = UserContextInterface::USER_TYPE_INTEGRATION;
        $this->callerUserId = 1;

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with('322')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->never())
            ->method('getActive');

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessageMatches(self::REFUSAL_MESSAGE_PATTERN);

        $this->checkoutManagement->create('322', $this->redirectPathStub);
    }

    /**
     * The refusal must be indistinguishable from the "cart does not exist" refusal above, so the
     * endpoint cannot be used to enumerate quote ids.
     */
    public function testNumericQuoteIdIsRefusedForNonOwningCustomer()
    {
        $this->callerUserId = self::CART_OWNER_ID + 1;

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with('322')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessageMatches(self::REFUSAL_MESSAGE_PATTERN);

        $this->checkoutManagement->create('322', $this->redirectPathStub);
    }

    public function testNonNumericUnmaskableCartIdIsRefused()
    {
        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with('322abc')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->never())
            ->method('getActive');

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);

        $this->checkoutManagement->create('322abc', $this->redirectPathStub);
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

    /**
     * Express checkout is driven by the session-scoped controller, which passes the numeric quote
     * id of the caller's own session for guests as well as customers. It must stay unrestricted.
     */
    public function testExpressCheckoutAcceptsNumericQuoteIdForGuestCaller()
    {
        $this->callerUserType = UserContextInterface::USER_TYPE_GUEST;
        $this->callerUserId = 0;

        $this->paymentMock->method('getAdditionalInformation')
            ->willReturn('fake-additional-information');

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with('322')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with(322)
            ->willReturn($this->quoteStub);

        $this->checkoutManagement->createExpress('322', 'https://test-clearpay.com/checkout/cart');
    }
}
