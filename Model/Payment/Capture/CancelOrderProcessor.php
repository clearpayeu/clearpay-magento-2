<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment\Capture;

use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;

class CancelOrderProcessor
{
    private \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private \Magento\Payment\Gateway\CommandInterface $voidCommand;
    private \Magento\Payment\Gateway\CommandInterface $reversalCommand;
    private \Magento\Store\Model\StoreManagerInterface $storeManager;
    private \Clearpay\Clearpay\Model\Config $config;
    private \Clearpay\Clearpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage;

    public function __construct(
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Magento\Payment\Gateway\CommandInterface                       $voidCommand,
        \Magento\Payment\Gateway\CommandInterface                       $reversalCommand,
        \Magento\Store\Model\StoreManagerInterface                      $storeManager,
        \Clearpay\Clearpay\Model\Config                                 $config,
        \Clearpay\Clearpay\Model\Order\Payment\QuotePaidStorage         $quotePaidStorage
    )
    {
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->voidCommand = $voidCommand;
        $this->reversalCommand = $reversalCommand;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->quotePaidStorage = $quotePaidStorage;
    }

    /**
     * @throws \Magento\Payment\Gateway\Command\CommandException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Quote\Model\Quote\Payment $payment, int $quoteId): void
    {
        $commandSubject = ['payment' => $this->paymentDataObjectFactory->create($payment)];

        if (!$this->isDeferredPaymentFlow()) {
            $this->reversalCommand->execute($commandSubject);

            return;
        }

        $clearpayPayment = $this->quotePaidStorage->getClearpayPaymentIfQuoteIsPaid($quoteId);
        if (!$clearpayPayment) {
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
