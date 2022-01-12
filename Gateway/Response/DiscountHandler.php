<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Response;

use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;
use Clearpay\Clearpay\Model\PaymentStateInterface;

class DiscountHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        $totalDiscount = $this->getOrderDiscountAmount($payment->getOrder());
        $paymentState = $payment->getAdditionalInformation(AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE);
        if ($paymentState == PaymentStateInterface::CAPTURED) {
            $rolloverDiscount = '0.00';
            $capturedDiscount = $totalDiscount;
        } else {
            $rolloverDiscount = $totalDiscount;
            $capturedDiscount = '0.00';
        }
        $payment->setAdditionalInformation(
            AdditionalInformationInterface::CLEARPAY_ROLLOVER_DISCOUNT,
            $rolloverDiscount
        );
        $payment->setAdditionalInformation(
            AdditionalInformationInterface::CLEARPAY_CAPTURED_DISCOUNT,
            $capturedDiscount
        );
    }

    protected function getOrderDiscountAmount(\Magento\Sales\Model\Order $order): float
    {
        return (float)($order->getBaseGiftCardsAmount() + $order->getBaseCustomerBalanceAmount());
    }
}
