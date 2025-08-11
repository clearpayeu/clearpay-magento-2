<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment\AmountProcessor;

use Magento\Store\Model\ScopeInterface;

class CreditMemo
{
    private const XML_PATH_AUTO_REFUND = 'customer/magento_customerbalance/refund_automatically';
    private \Clearpay\Clearpay\Model\Order\OrderItemProvider $orderItemProvider;
    private \Magento\Weee\Block\Item\Price\Renderer $priceRenderer;
    private \Clearpay\Clearpay\Model\Config $config;
    private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig;

    public function __construct(
        \Clearpay\Clearpay\Model\Order\OrderItemProvider $orderItemProvider,
        \Magento\Weee\Block\Item\Price\Renderer $priceRenderer,
        \Clearpay\Clearpay\Model\Config $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->orderItemProvider = $orderItemProvider;
        $this->priceRenderer = $priceRenderer;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
    }

    public function process(\Magento\Sales\Model\Order\Payment $payment): array
    {
        $amountToRefund = $amountToVoid = 0;

        if ($this->config->getIsCreditMemoGrandTotalOnlyEnabled((int)$payment->getOrder()->getStore()->getWebsiteId(), true)) {// @codingStandardsIgnoreLine
            $this->processWithGrandTotal($payment, $amountToVoid, $amountToRefund);
        } else {
            $this->processWithSeparateCalculations($payment, $amountToVoid, $amountToRefund);
        }

        $this->processStoreCredit($payment);

        return [$amountToRefund, $amountToVoid];
    }

    private function processWithSeparateCalculations(
        \Magento\Sales\Model\Order\Payment $payment,
        float                              &$amountToVoid,
        float                              &$amountToRefund
    ): void {

        $creditmemo = $payment->getCreditmemo();
        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();

            if (!$creditmemoItem->getBaseRowTotalInclTax()) {
                continue;
            }

            if ($orderItem->getIsVirtual()) {
                $amountToRefund += $this->calculateItemPrice($payment, $creditmemoItem, (float)$creditmemoItem->getQty());
                continue;
            }

            if ($this->getItemCapturedQty($orderItem) <= 0) {
                $amountToVoid += $this->calculateItemPrice($payment, $creditmemoItem, (float)$creditmemoItem->getQty());
                continue;
            }

            $orderItemQtyRefunded = $orderItem->getOrigData('qty_refunded');
            if (!(float)$orderItemQtyRefunded) {
                $this->processForCapturedButNotRefunded($payment, $orderItem, $creditmemoItem, $amountToRefund, $amountToVoid);
                continue;
            }

            $this->processForCapturedAndRefunded($payment, $orderItem, $creditmemoItem, $amountToRefund, $amountToVoid);
        }

        $this->processShipmentAmount($payment, $creditmemo, $amountToRefund, $amountToVoid);
        $this->processCapturedDiscountForRefundAmount($payment, $amountToRefund);
        $this->processRolloverDiscountForVoidAmount($payment, $amountToVoid);
        $this->processAdjustmentAmount($payment, $amountToVoid, $amountToRefund);

    }

    private function processAdjustmentAmount(
        \Magento\Sales\Model\Order\Payment $payment,
        float                              &$amountToVoid,
        float                              &$amountToRefund): void
    {
        $additionalInfo = $payment->getAdditionalInformation();
        $paymentState = $additionalInfo[\Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE] ?? '';// @codingStandardsIgnoreLine
        $creditmemo = $payment->getCreditmemo();

        if ($paymentState === \Clearpay\Clearpay\Model\PaymentStateInterface::AUTH_APPROVED) {
            $amountToVoid += $creditmemo->getAdjustmentPositive();
            $amountToVoid -= $creditmemo->getAdjustmentNegative();
            return;
        }

        if ($paymentState === \Clearpay\Clearpay\Model\PaymentStateInterface::CAPTURED
            || $paymentState === \Clearpay\Clearpay\Model\PaymentStateInterface::PARTIALLY_CAPTURED) {
            $amountToRefund += $creditmemo->getAdjustmentPositive();
            $amountToRefund -= $creditmemo->getAdjustmentNegative();
        }
    }

    private function processForCapturedButNotRefunded(
        \Magento\Sales\Model\Order\Payment         $payment,
        \Magento\Sales\Model\Order\Item            $orderItem,
        \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem,
        float                                      &$amountToRefund,
        float                                      &$amountToVoid
    ): void
    {
        $itemCapturedQty = $this->getItemCapturedQty($orderItem);
        if ($itemCapturedQty >= $creditmemoItem->getQty()) {
            $amountToRefund += $this->calculateItemPrice($payment, $creditmemoItem, (float)$creditmemoItem->getQty());
        } else {
            $amountToRefund += $this->calculateItemPrice($payment, $creditmemoItem, (float)$itemCapturedQty);
            $amountToVoid += $this->calculateItemPrice(
                $payment,
                $creditmemoItem,
                (float)($creditmemoItem->getQty() - $itemCapturedQty)
            );
        }
    }

    private function processForCapturedAndRefunded(
        \Magento\Sales\Model\Order\Payment         $payment,
        \Magento\Sales\Model\Order\Item            $orderItem,
        \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem,
        float                                      &$amountToRefund,
        float                                      &$amountToVoid
    ): void
    {
        $clearpayOrderItemHistory = $this->orderItemProvider->provide($orderItem);
        $itemCapturedQty = $this->getItemCapturedQty($orderItem);
        $allowedToRefundQty = $itemCapturedQty - $clearpayOrderItemHistory->getClearpayRefundedQty();
        if ($allowedToRefundQty > 0) {
            if ($creditmemoItem->getQty() > $allowedToRefundQty) {
                $amountToRefund += $this->calculateItemPrice($payment, $creditmemoItem, (float)$allowedToRefundQty);
                $amountToVoid += $this->calculateItemPrice(
                    $payment,
                    $creditmemoItem,
                    (float)($creditmemoItem->getQty() - $allowedToRefundQty)
                );
            } else {
                $amountToRefund += $this->calculateItemPrice($payment, $creditmemoItem, (float)$creditmemoItem->getQty());
            }
        } else {
            $amountToVoid += $this->calculateItemPrice($payment, $creditmemoItem, (float)$creditmemoItem->getQty());
        }
    }

    private function processShipmentAmount(
        \Magento\Sales\Model\Order\Payment    $payment,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
        float                                 &$amountToRefund,
        float                                 &$amountToVoid
    ): void
    {
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
        float                              &$amountToRefund
    ): void
    {
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
        float                              &$amountToVoid
    ): void
    {
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

    private function calculateItemPrice(
        \Magento\Sales\Model\Order\Payment         $payment,
        \Magento\Sales\Model\Order\Creditmemo\Item $item,
        float                                      $qty
    ): float
    {
        $isCBTCurrency = $payment->getAdditionalInformation(\Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_IS_CBT_CURRENCY);// @codingStandardsIgnoreLine
        $rowTotal = $isCBTCurrency ? $this->priceRenderer->getTotalAmount($item) : $this->priceRenderer->getBaseTotalAmount($item);// @codingStandardsIgnoreLine
        $pricePerItem = $rowTotal / $item->getQty();

        return $qty * $pricePerItem;
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

    private function processWithGrandTotal(
        \Magento\Sales\Model\Order\Payment $payment,
        float                              &$amountToVoid,
        float                              &$amountToRefund
    ): void
    {
        $isCBTCurrency = $payment->getAdditionalInformation(\Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_IS_CBT_CURRENCY);// @codingStandardsIgnoreLine
        $paymentState = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE); // @codingStandardsIgnoreLine
        $creditmemo = $payment->getCreditmemo();
        $amount = $isCBTCurrency ? $creditmemo->getGrandTotal() : $creditmemo->getBaseGrandTotal();

        switch ($paymentState) {
            case \Clearpay\Clearpay\Model\PaymentStateInterface::AUTH_APPROVED:
                $amountToVoid += $amount;
                break;
            case \Clearpay\Clearpay\Model\PaymentStateInterface::PARTIALLY_CAPTURED:
                $openToCapture = $payment->getAdditionalInformation(
                    \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_OPEN_TO_CAPTURE_AMOUNT
                );
                $orderAmount = $isCBTCurrency ? $payment->getOrder()->getGrandTotal() : $payment->getOrder()->getBaseGrandTotal();

                if ($amount == $orderAmount) {
                    if ($openToCapture && $amount > $openToCapture) {
                        $amountToVoid += $openToCapture;
                        $amountToRefund += $amount - $openToCapture;
                    } else {
                        $amountToRefund += $amount;
                    }
                } else {
                    $this->processWithSeparateCalculations($payment, $amountToVoid, $amountToRefund);
                }
                break;
            case \Clearpay\Clearpay\Model\PaymentStateInterface::CAPTURED:
            default:
                $amountToRefund += $amount;
                break;
        }
    }

    private function processStoreCredit(
        \Magento\Sales\Model\Order\Payment $payment
    ): void {
        if ($this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_REFUND, ScopeInterface::SCOPE_STORE)) {
            $creditmemo = $payment->getCreditmemo();
            if ($creditmemo) {
                $creditmemo->setCustomerBalanceRefundFlag(true)
                    ->setCustomerBalTotalRefunded($creditmemo->getCustomerBalanceAmount())
                    ->setBsCustomerBalTotalRefunded($creditmemo->getBaseCustomerBalanceAmount())
                    ->setCustomerBalanceRefunded($creditmemo->getCustomerBalanceAmount())
                    ->setBaseCustomerBalanceRefunded($creditmemo->getBaseCustomerBalanceAmount());
            }
        }
    }
}
