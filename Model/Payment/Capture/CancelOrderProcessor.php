<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment\Capture;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Quote\Model\Quote\Payment;

class CancelOrderProcessor
{
    private $paymentDataObjectFactory;
    private $reversalCommand;
    private $voidCommand;
    private $storeManager;
    private $config;
    private $quotePaidStorage;

    public function __construct(
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Magento\Payment\Gateway\CommandInterface                       $reversalCommand,
        \Magento\Payment\Gateway\CommandInterface                       $voidCommand,
        \Magento\Store\Model\StoreManagerInterface                      $storeManager,
        \Clearpay\Clearpay\Model\Config                                 $config,
        \Clearpay\Clearpay\Model\Order\Payment\QuotePaidStorage         $quotePaidStorage
    ) {
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->reversalCommand = $reversalCommand;
        $this->voidCommand = $voidCommand;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->quotePaidStorage = $quotePaidStorage;
    }

    /**
     * @param Payment         $payment
     * @param int             $quoteId
     * @param \Throwable|null $e
     *
     * @throws CommandException
     * @throws LocalizedException
     * @throws \Throwable
     */
    public function execute(\Magento\Quote\Model\Quote\Payment $payment, int $quoteId, \Throwable $e = null): void
    {
        if (!$this->config->getIsReversalEnabled()) {
            return;
        }

        $commandSubject = ['payment' => $this->paymentDataObjectFactory->create($payment)];

        if (!$this->isDeferredPaymentFlow()) {
            $this->reversalCommand->execute($commandSubject);

            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'There was a problem placing your order. Your Clearpay order %1 is refunded.',
                    $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_ORDER_ID)
                )
            );
        }

        $clearpayPayment = $this->quotePaidStorage->getClearpayPaymentIfQuoteIsPaid($quoteId);
        if (!$clearpayPayment) {
            if ($e instanceof LocalizedException) {
                throw $e;
            }

            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'Clearpay payment is declined. Please select an alternative payment method.'
                )
            );
        }

        $commandSubject = ['payment' => $this->paymentDataObjectFactory->create($clearpayPayment)];
        $this->voidCommand->execute($commandSubject);
    }

    private function isDeferredPaymentFlow(): bool
    {
        $websiteId = (int)$this->storeManager->getStore()->getWebsiteId();
        $paymentFlow = $this->config->getPaymentFlow($websiteId);

        return $paymentFlow === \Clearpay\Clearpay\Model\Config\Source\PaymentFlow::DEFERRED;
    }
}
