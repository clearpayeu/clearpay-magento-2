<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment\AmountProcessor;

class VirtualProducts extends \Clearpay\Clearpay\Model\Payment\AmountProcessor\Order
{
    protected function processDiscount(float $amount, \Magento\Payment\Model\InfoInterface $payment): float
    {
        $capturedDiscount = $payment->getAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_CAPTURED_DISCOUNT
        ) ?? 0;
        $totalDiscountAmount = $payment->getAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_ROLLOVER_DISCOUNT
        ) ?? 0;

        $isCBTCurrency = $payment->getAdditionalInformation(\Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_IS_CBT_CURRENCY);
        $orderTotal = $isCBTCurrency ? $payment->getOrder()->getGrandTotal() : $payment->getOrder()->getBaseGrandTotal();
        $totalWithoutVirtualProducts = $orderTotal - $amount;
        $returnAmount = $amount;

        if ($amount > $totalDiscountAmount) {
            $returnAmount -= $totalDiscountAmount;
            $capturedDiscount += $totalDiscountAmount;
            $totalDiscountAmount = '0.00';
        } else {
            if ($totalWithoutVirtualProducts < $totalDiscountAmount) {
                $returnAmount = $orderTotal;
            }

            $openToCapture = $payment->getAdditionalInformation(
                \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_OPEN_TO_CAPTURE_AMOUNT
            );
            if ($openToCapture && $openToCapture == $returnAmount) {
                $capturedDiscount += $totalDiscountAmount;
                $totalDiscountAmount = '0.00';
            }
        }

        $payment->setAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_ROLLOVER_DISCOUNT,
            (string)$totalDiscountAmount
        );
        $payment->setAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_CAPTURED_DISCOUNT,
            $capturedDiscount
        );

        return $returnAmount;
    }
}
