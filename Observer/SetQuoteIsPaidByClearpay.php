<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Observer;

class SetQuoteIsPaidByClearpay implements \Magento\Framework\Event\ObserverInterface
{
    private \Clearpay\Clearpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage;

    public function __construct(
        \Clearpay\Clearpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage
    ) {
        $this->quotePaidStorage = $quotePaidStorage;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getEvent()->getData('payment');

        if ($payment->getMethod() == \Clearpay\Clearpay\Gateway\Config\Config::CODE) {
            $this->quotePaidStorage->setClearpayPaymentForQuote((int)$payment->getOrder()->getQuoteId(), $payment);
        }
    }
}
