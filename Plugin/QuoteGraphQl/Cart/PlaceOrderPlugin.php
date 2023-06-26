<?php

namespace Clearpay\Clearpay\Plugin\QuoteGraphQl\Cart;

use Clearpay\Clearpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface;
use Clearpay\Clearpay\Model\Payment\PaymentErrorProcessor;
use Magento\QuoteGraphQl\Model\Cart\PlaceOrder as PlaceOrderModel;

class PlaceOrderPlugin
{
    private PaymentErrorProcessor $paymentErrorProcessor;
    private CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability;

    public function __construct(
        CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability,
        PaymentErrorProcessor                 $paymentErrorProcessor
    ) {
        $this->paymentErrorProcessor = $paymentErrorProcessor;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
    }

    public function aroundExecute(
        PlaceOrderModel $subject,
        callable        $proceed,
                        $cart,
                        $maskedCartId,
                        $userId
    ) {
        try {
            $payment = $cart->getPayment();
            if ($payment->getMethod() === 'clearpay') {
                $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($cart);
                $payment->setAdditionalInformation(
                    \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_IS_CBT_CURRENCY,
                    $isCBTCurrencyAvailable
                );
                $payment->setAdditionalInformation(
                    \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_CBT_CURRENCY,
                    $cart->getQuoteCurrencyCode()
                );
            }

            return $proceed($cart, $maskedCartId, $userId);
        } catch (\Throwable $e) {
            if ($payment->getMethod() === 'clearpay') {
                return (int)$this->paymentErrorProcessor->execute($cart, $e, $payment);
            }

            throw $e;
        }
    }
}
