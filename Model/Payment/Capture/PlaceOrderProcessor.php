<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment\Capture;

use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Quote\Model\Quote;

class PlaceOrderProcessor
{
    private \Magento\Quote\Api\CartManagementInterface $cartManagement;
    private \Clearpay\Clearpay\Model\Payment\Capture\CancelOrderProcessor $cancelOrderProcessor;
    private \Clearpay\Clearpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage;
    private \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private \Clearpay\Clearpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability;
    private \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Clearpay\Clearpay\Model\Payment\Capture\CancelOrderProcessor $cancelOrderProcessor,
        \Clearpay\Clearpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Clearpay\Clearpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface  $checkCBTCurrencyAvailability,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->cartManagement = $cartManagement;
        $this->cancelOrderProcessor = $cancelOrderProcessor;
        $this->quotePaidStorage = $quotePaidStorage;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
        $this->logger = $logger;
    }

    public function execute(Quote $quote, CommandInterface $checkoutDataCommand, string $clearpayOrderToken): void
    {
        try {
            $quote->getPayment()->setAdditionalInformation(
                \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_TOKEN,
                $clearpayOrderToken
            );

            $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
            $quote->getPayment()->setAdditionalInformation(
                \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_IS_CBT_CURRENCY,
                $isCBTCurrencyAvailable
            );
            $quote->getPayment()->setAdditionalInformation(
                \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_CBT_CURRENCY,
                $quote->getQuoteCurrencyCode()
            );

            if (!$quote->getCustomerId()) {
                $quote->setCustomerEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
            }

            $checkoutDataCommand->execute(['payment' => $this->paymentDataObjectFactory->create($quote->getPayment())]);

            $this->cartManagement->placeOrder($quote->getId());
        } catch (\Throwable $e) {
            $this->logger->critical('Order placement is failed with error: ' . $e->getMessage());
            $quoteId = (int)$quote->getId();
            if ($clearpayPayment = $this->quotePaidStorage->getClearpayPaymentIfQuoteIsPaid($quoteId)) {
                $this->cancelOrderProcessor->execute($clearpayPayment);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __(
                        'There was a problem placing your order. Your %1 order %2 is refunded.',
                        $quote->getPayment()->getMethodInstance()->getTitle(),
                        $quote->getPayment()->getAdditionalInformation(AdditionalInformationInterface::CLEARPAY_ORDER_ID)
                    )
                );
            }
            throw $e;
        }
    }
}
