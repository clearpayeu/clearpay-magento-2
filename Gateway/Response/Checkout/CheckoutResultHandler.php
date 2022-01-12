<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Response\Checkout;

class CheckoutResultHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    public function handle(array $handlingSubject, array $response): void
    {
        $payment = $this->getPayment($handlingSubject);

        $payment->setAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_TOKEN,
            $response['token']
        );
        $payment->setAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_AUTH_TOKEN_EXPIRES,
            $response['expires']
        );
        $payment->setAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_REDIRECT_CHECKOUT_URL,
            $response['redirectCheckoutUrl']
        );
    }

    protected function getPayment(array $handlingSubject): \Magento\Payment\Model\InfoInterface
    {
        $quote = $handlingSubject['quote'];
        return $quote->getPayment();
    }
}
