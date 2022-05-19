<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment\AmountProcessor;

class CreditMemo
{
    private \Clearpay\Clearpay\Model\Order\OrderItemProvider $orderItemProvider;

    public function __construct(
        \Clearpay\Clearpay\Model\Order\OrderItemProvider $orderItemProvider
    ) {
        $this->orderItemProvider = $orderItemProvider;
    }

    public function process(\Magento\Sales\Model\Order\Payment $payment): array
    {
        $amountToRefund = $amountToVoid = 0;
        $creditmemo = $payment->getCreditmemo();
        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();

            if (!$creditmemoItem->getBaseRowTotalInclTax()) {
                continue;
            }

            if ($orderItem->getIsVirtual()) {
                $amountToRefund += $this->calculateItemPrice($creditmemoItem, (float)$creditmemoItem->getQty());
                continue;
            }

            if ($this->getItemCapturedQty($orderItem) <= 0) {
                $amountToVoid += $this->calculateItemPrice($creditmemoItem, (float)$creditmemoItem->getQty());
                continue;
            }

            $orderItemQtyRefunded = $orderItem->getOrigData('qty_refunded');
            if (!(float)$orderItemQtyRefunded) {
                $this->processForCapturedButNotRefunded($orderItem, $creditmemoItem, $amountToRefund, $amountToVoid);
                continue;
            }

            $this->processForCapturedAndRefunded($orderItem, $creditmemoItem, $amountToRefund, $amountToVoid);
        }

        $this->processShipmentAmount($payment, $creditmemo, $amountToRefund, $amountToVoid);

        $this->processCapturedDiscountForRefundAmount($payment, $amountToRefund);
        $this->processRolloverDiscountForVoidAmount($payment, $amountToVoid);

        return [$amountToRefund, $amountToVoid];
    }

    private function processForCapturedButNotRefunded(
        \Magento\Sales\Model\Order\Item $orderItem,
        \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem,
        float &$amountToRefund,
        float &$amountToVoid
    ): void {
        $itemCapturedQty = $this->getItemCapturedQty($orderItem);
        if ($itemCapturedQty >= $creditmemoItem->getQty()) {
            $amountToRefund += $this->calculateItemPrice($creditmemoItem, (float)$creditmemoItem->getQty());
        } else {
            $amountToRefund += $this->calculateItemPrice($creditmemoItem, (float)$itemCapturedQty);
            $amountToVoid += $this->calculateItemPrice(
                $creditmemoItem,
                (float)($creditmemoItem->getQty() - $itemCapturedQty)
            );
        }
    }

    private function processForCapturedAndRefunded(
        \Magento\Sales\Model\Order\Item $orderItem,
        \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem,
        float &$amountToRefund,
        float &$amountToVoid
    ): void {
        $clearpayOrderItemHistory = $this->orderItemProvider->provide($orderItem);
        $itemCapturedQty = $this->getItemCapturedQty($orderItem);
        $allowedToRefundQty = $itemCapturedQty - $clearpayOrderItemHistory->getClearpayRefundedQty();
        if ($allowedToRefundQty > 0) {
            if ($creditmemoItem->getQty() > $allowedToRefundQty) {
                $amountToRefund += $this->calculateItemPrice($creditmemoItem, (float)$allowedToRefundQty);
                $amountToVoid += $this->calculateItemPrice(
                    $creditmemoItem,
                    (float)($creditmemoItem->getQty() - $allowedToRefundQty)
                );
            } else {
                $amountToRefund += $this->calculateItemPrice($creditmemoItem, (float)$creditmemoItem->getQty());
            }
        } else {
            $amountToVoid += $this->calculateItemPrice($creditmemoItem, (float)$creditmemoItem->getQty());
        }
    }

    private function processShipmentAmount(
        \Magento\Sales\Model\Order\Payment $payment,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
        float &$amountToRefund,
        float &$amountToVoid
    ): void {
        $paymentState = $payment->getAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE
        );
        if ($paymentState == \Clearpay\Clearpay\Model\PaymentStateInterface::CAPTURED) {
            $amountToRefund += $creditmemo->getShippingInclTax();
            return;
        }

        if ($payment->getOrder()->getShipmentsCollection()->count()) {
            $amountToRefund += $creditmemo->getShippingInclTax();
        } else {
            $amountToVoid += $creditmemo->getShippingInclTax();
        }
    }

    private function processCapturedDiscountForRefundAmount(
        \Magento\Sales\Model\Order\Payment $payment,
        float &$amountToRefund
    ): void {
        $capturedDiscount = $payment->getAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_CAPTURED_DISCOUNT
        );
        if ($amountToRefund > 0 && $capturedDiscount > 0) {
            if ($capturedDiscount <= $amountToRefund) {
                $amountToRefund -= $capturedDiscount;
                $capturedDiscount = 0;
            } else {
                $capturedDiscount -= $amountToRefund;
                $amountToRefund = 0;
            }
            $payment->setAdditionalInformation(
                \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_CAPTURED_DISCOUNT,
                $capturedDiscount
            );
        }
    }

    private function processRolloverDiscountForVoidAmount(
        \Magento\Sales\Model\Order\Payment $payment,
        float &$amountToVoid
    ): void {
        $rolloverDiscount = $payment->getAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_ROLLOVER_DISCOUNT
        );
        if ($rolloverDiscount > 0 && $amountToVoid > 0) {
            if ($rolloverDiscount <= $amountToVoid) {
                $amountToVoid -= $rolloverDiscount;
                $rolloverDiscount = 0;
            } else {
                $rolloverDiscount -= $amountToVoid;
                $amountToVoid = 0;
            }
            $payment->setAdditionalInformation(
                \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_ROLLOVER_DISCOUNT,
                $rolloverDiscount
            );
        }
    }

    private function calculateItemPrice(\Magento\Sales\Model\Order\Creditmemo\Item $item, float $qty): float
    {
        $discountPerItem = $item->getBaseDiscountAmount() / $item->getQty();
        $pricePerItem = ($item->getBaseRowTotal() + $item->getBaseTaxAmount()) / $item->getQty();
        return $qty * ($pricePerItem - $discountPerItem);
    }

    private function getItemCapturedQty(\Magento\Sales\Model\Order\Item $item): float
    {
        $paymentState = $item->getOrder()->getPayment()->getAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE
        );
        switch ($paymentState) {
            case \Clearpay\Clearpay\Model\PaymentStateInterface::CAPTURED:
                return (float)$item->getQtyOrdered();
            case \Clearpay\Clearpay\Model\PaymentStateInterface::AUTH_APPROVED:
            case \Clearpay\Clearpay\Model\PaymentStateInterface::PARTIALLY_CAPTURED:
                return $item->getParentItem()
                    ? (float)$item->getParentItem()->getQtyShipped()
                    : (float)$item->getQtyShipped();
        }
        return 0;
    }
}
