<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Order\CreditMemo;

use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;
use Clearpay\Clearpay\Model\PaymentStateInterface;

class CreditMemoProcessor
{
    private \Clearpay\Clearpay\Model\Order\Payment\Auth\ExpiryDate $expiryDate;
    private \Clearpay\Clearpay\Model\Order\CreditMemo\CreditMemoInitiator $creditMemoInitiator;
    private \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement;
    private OrderUpdater $orderUpdater;
    private PaymentUpdater $paymentUpdater;

    public function __construct(
        \Clearpay\Clearpay\Model\Order\Payment\Auth\ExpiryDate $expiryDate,
        \Clearpay\Clearpay\Model\Order\CreditMemo\CreditMemoInitiator $creditMemoInitiator,
        \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement,
        OrderUpdater $orderUpdater,
        PaymentUpdater $paymentUpdater
    ) {
        $this->expiryDate = $expiryDate;
        $this->creditMemoInitiator = $creditMemoInitiator;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->orderUpdater = $orderUpdater;
        $this->paymentUpdater = $paymentUpdater;
    }

    public function processOrder(\Magento\Sales\Model\Order $order): void
    {
        $additionalInformation = $order->getData('additional_information');
        $expireDate = $additionalInformation[
            AdditionalInformationInterface::CLEARPAY_AUTH_EXPIRY_DATE
        ];
        if (!$this->expiryDate->isExpired($expireDate)) {
            return;
        }
        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $order->getPayment();
        $payment = $this->paymentUpdater->updatePayment($payment);
        $additionalInformation = $payment->getAdditionalInformation();
        $paymentState = $additionalInformation[
            AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE
        ];
        if ($paymentState !== PaymentStateInterface::CAPTURED &&
            $paymentState !== PaymentStateInterface::PARTIALLY_CAPTURED &&
            $paymentState !== PaymentStateInterface::VOIDED) {
            return;
        }
        $creditmemo = $this->creditMemoInitiator->init($order);
        $this->creditmemoManagement->refund($creditmemo, true);
        $this->orderUpdater->updateOrder($order);
    }
}
