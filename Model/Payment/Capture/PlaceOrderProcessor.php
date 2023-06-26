<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment\Capture;

use Clearpay\Clearpay\Api\Data\CheckoutInterface;
use Clearpay\Clearpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface;
use Clearpay\Clearpay\Model\Order\Payment\Auth\TokenValidator;
use Clearpay\Clearpay\Model\Payment\PaymentErrorProcessor;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;

class PlaceOrderProcessor
{
    private $cartManagement;
    private $paymentDataObjectFactory;
    private $checkCBTCurrencyAvailability;
    private $tokenValidator;
    private $paymentErrorProcessor;

    public function __construct(
        CartManagementInterface               $cartManagement,
        PaymentDataObjectFactoryInterface     $paymentDataObjectFactory,
        CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability,
        TokenValidator                        $tokenValidator,
        PaymentErrorProcessor                 $paymentErrorProcessor
    ) {
        $this->cartManagement = $cartManagement;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
        $this->tokenValidator = $tokenValidator;
        $this->paymentErrorProcessor = $paymentErrorProcessor;
    }

    public function execute(Quote $quote, CommandInterface $checkoutDataCommand, string $clearpayOrderToken): void
    {
        if ($this->tokenValidator->checkIsUsed($clearpayOrderToken)) {
            return;
        }

        $payment = $quote->getPayment();
        try {
            $payment->setAdditionalInformation(CheckoutInterface::CLEARPAY_TOKEN, $clearpayOrderToken);

            $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
            $payment->setAdditionalInformation(CheckoutInterface::CLEARPAY_IS_CBT_CURRENCY, $isCBTCurrencyAvailable);
            $payment->setAdditionalInformation(CheckoutInterface::CLEARPAY_CBT_CURRENCY, $quote->getQuoteCurrencyCode());

            if (!$quote->getCustomerId()) {
                $quote->setCustomerEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
            }

            $checkoutDataCommand->execute(['payment' => $this->paymentDataObjectFactory->create($payment)]);

            $this->cartManagement->placeOrder($quote->getId());
        } catch (\Throwable $e) {
            $this->paymentErrorProcessor->execute($quote, $e, $payment);
        }
    }
}
