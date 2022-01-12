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
    private \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Clearpay\Clearpay\Model\Payment\Capture\CancelOrderProcessor $cancelOrderProcessor,
        \Clearpay\Clearpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->cartManagement = $cartManagement;
        $this->cancelOrderProcessor = $cancelOrderProcessor;
        $this->quotePaidStorage = $quotePaidStorage;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->logger = $logger;
    }

    public function execute(Quote $quote, CommandInterface $checkoutDataCommand, string $clearpayOrderToken): void
    {
        try {
            $quote->getPayment()->setAdditionalInformation(
                \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_TOKEN,
                $clearpayOrderToken
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
                        'There was a problem placing your order. Your Clearpay order %1 is refunded.',
                        $clearpayPayment->getAdditionalInformation(AdditionalInformationInterface::CLEARPAY_ORDER_ID)
                    )
                );
            }
            throw $e;
        }
    }
}
