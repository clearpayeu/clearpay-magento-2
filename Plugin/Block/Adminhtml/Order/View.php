<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Plugin\Block\Adminhtml\Order;

class View
{
    /**
     * @param string $buttonId
     * @param \Magento\Sales\Block\Adminhtml\Order\View $result
     * @return \Magento\Sales\Block\Adminhtml\Order\View
     */
    public function afterAddButton(
        \Magento\Sales\Block\Adminhtml\Order\View $orderView,
        $result,
        $buttonId
    ) {
        if ($buttonId !== 'order_creditmemo') {
            return $result;
        }
        $order = $orderView->getOrder();
        $payment = $order->getPayment();
        if ($payment == null) {
            return $result;
        }
        if ($payment->getMethod() === \Clearpay\Clearpay\Gateway\Config\Config::CODE
            && $buttonId == 'order_creditmemo') {
            $orderView->removeButton($buttonId);
        }
        return $result;
    }
}
