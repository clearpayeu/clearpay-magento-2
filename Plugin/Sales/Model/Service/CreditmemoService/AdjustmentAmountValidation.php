<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Plugin\Sales\Model\Service\CreditmemoService;

class AdjustmentAmountValidation
{
    public function beforeRefund(
        \Magento\Sales\Api\CreditmemoManagementInterface $subject,
        \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo,
        $offlineRequested = false
    ) {
        $order = $creditmemo->getOrder();
        if (($creditmemo->getBaseAdjustmentPositive() != 0 || $creditmemo->getBaseAdjustmentNegative() != 0)
            && $order->getPayment()->getMethod() === \Clearpay\Clearpay\Gateway\Config\Config::CODE
            && $order->getState() !== \Magento\Sales\Model\Order::STATE_COMPLETE
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("You can't use Adjustment amount for order with status that isn't complete for the current payment method")
            );
        }
        return [$creditmemo, $offlineRequested];
    }
}
