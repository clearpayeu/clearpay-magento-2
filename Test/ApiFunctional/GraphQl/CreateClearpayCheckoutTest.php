<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Test\ApiFunctional\GraphQl;

use Clearpay\Clearpay\Api\Data\CheckoutInterface;

class CreateClearpayCheckoutTest extends \Magento\TestFramework\TestCase\GraphQlAbstract
{
    /**
     * @var \Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId|mixed
     */
    private $getMaskedQuoteIdByReservedOrderId;

    protected function setUp(): void
    {
        $this->getMaskedQuoteIdByReservedOrderId = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->get(\Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId::class);
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/guest_quote_with_addresses.php
     * @magentoConfigFixture default/payment/clearpay/active 1
     */
    public function testCreateClearpayCheckoutReturnData()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('guest_quote');

        $mutation = $this->createClearpayCheckoutMutation($maskedQuoteId);
        $response = $this->graphQlMutation($mutation);

        self::assertArrayHasKey(CheckoutInterface::CLEARPAY_TOKEN, $response['createClearpayCheckout']);
        self::assertArrayHasKey(CheckoutInterface::CLEARPAY_AUTH_TOKEN_EXPIRES, $response['createClearpayCheckout']);
        self::assertArrayHasKey(CheckoutInterface::CLEARPAY_REDIRECT_CHECKOUT_URL, $response['createClearpayCheckout']);
    }

    public function testNoSuchCartException()
    {
        $emptyMaskedCartId = '';
        $mutation = $this->createClearpayCheckoutMutation($emptyMaskedCartId);

        self::expectExceptionMessageMatches('/No such entity.*/');
        $this->graphQlMutation($mutation);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     * @magentoConfigFixture default/payment/clearpay/active 0
     */
    public function testPaymentIsNotActiveException()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_order_1');
        $mutation = $this->createClearpayCheckoutMutation($maskedQuoteId);

        self::expectExceptionMessage('Clearpay payment method is not active');
        $this->graphQlMutation($mutation);
    }

    private function createClearpayCheckoutMutation(string $maskedCartId): string
    {
        return <<<QUERY
mutation {
    createClearpayCheckout(input: {
        cart_id: "{$maskedCartId}"
        redirect_path: {
            cancel_path: "frontend/cancel/path"
            confirm_path: "frontend/confirm/path"
        }
    }) {
        clearpay_token
        clearpay_expires
        clearpay_redirectCheckoutUrl
    }
}
QUERY;
    }
}
