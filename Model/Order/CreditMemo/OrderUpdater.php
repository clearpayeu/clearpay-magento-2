<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Order\CreditMemo;

use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;
use Clearpay\Clearpay\Model\PaymentStateInterface;

class OrderUpdater
{
    private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }
    public function updateOrder(
        \Magento\Sales\Model\Order $order
    ): \Magento\Sales\Model\Order {
        $payment = $order->getPayment();
        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $payment */
        $additionalInformation = $payment->getAdditionalInformation();
        $paymentState = $additionalInformation[
            AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE
        ];
        if ($paymentState == PaymentStateInterface::CAPTURED) {
            $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
        }
        $this->orderRepository->save($order);
        return $order;
    }
}
