<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Observer;

use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;
use Clearpay\Clearpay\Model\PaymentStateInterface;

class AuthCaptureAfterCreditMemo implements \Magento\Framework\Event\ObserverInterface
{
    private $captureProcessor;

    public function __construct(
        \Clearpay\Clearpay\Model\Order\CreditMemo\CaptureProcessor $captureProcessor
    ) {
        $this->captureProcessor = $captureProcessor;
    }

    /**
     * @throws \Magento\Framework\Exception\PaymentException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditMemo */
        $creditMemo = $observer->getEvent()->getData('creditmemo');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $paymentInfo = $creditMemo->getOrder();
        /** @var \Magento\Sales\Model\Order\Payment $paymentInfo */
        $paymentInfo = $order->getPayment();

        if ($paymentInfo->getMethod() !== \Clearpay\Clearpay\Gateway\Config\Config::CODE
            || $order->canShip() === true) {
            return;
        }

        $additionalInfo = $paymentInfo->getAdditionalInformation();
        $paymentState = $additionalInfo[AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE] ?? '';

        if ($paymentState !== PaymentStateInterface::AUTH_APPROVED &&
            $paymentState !== PaymentStateInterface::PARTIALLY_CAPTURED) {
            return;
        }

        $totalRefunded = $order->getTotalRefunded();
        $amountAuthorized = $paymentInfo->getAmountAuthorized();
        $shippingDiscountAmount = $order->getShippingDiscountAmount();
        $shippingTaxAmount = $order->getShippingTaxAmount();
        $shippingAmount = $paymentInfo->getShippingAmount() - $shippingDiscountAmount + $shippingTaxAmount;
        $remainAmount = $amountAuthorized - $totalRefunded;

        if (!$paymentInfo->getShippingAmount() || $remainAmount - $shippingAmount > 0.001) {
            return;
        }

        // The remaining amount is captured if it's less or equal to the shipping amount.
        // And there are no products which can be shipped
        $this->captureProcessor->execute($remainAmount, $paymentInfo);
    }
}
