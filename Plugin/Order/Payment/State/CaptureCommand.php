<?php
declare(strict_types=1);

namespace Clearpay\Clearpay\Plugin\Order\Payment\State;

use Magento\Sales\Model\Order;

/**
 * Changes order status state history for Clearpay order(deffered flow).
 */
class CaptureCommand
{
    private \Magento\Sales\Model\Order\StatusResolver $statusResolver;
    private \Clearpay\Clearpay\Model\Config $config;

    public function __construct(
        \Magento\Sales\Model\Order\StatusResolver $statusResolver,
        \Clearpay\Clearpay\Model\Config           $config
    ) {
        $this->statusResolver = $statusResolver;
        $this->config = $config;
    }

    public function aroundExecute(
        \Magento\Sales\Model\Order\Payment\State\CaptureCommand $subject,
        callable                                                $proceed,
        \Magento\Sales\Api\Data\OrderPaymentInterface           $payment,
                                                                $amount,
        \Magento\Sales\Api\Data\OrderInterface                  $order
    ): \Magento\Framework\Phrase {
        if ($payment->getMethod() === \Clearpay\Clearpay\Gateway\Config\Config::CODE) {
            $state = Order::STATE_PROCESSING;
            $status = null;
            $message = $this->config->getPaymentFlow() == \Clearpay\Clearpay\Model\Config\Source\PaymentFlow::DEFERRED ?
                'Authorized and open to capture amount of %1 online.' :
                'Captured amount of %1 online.';

            if ($payment->getIsTransactionPending()) {
                $state = Order::STATE_PAYMENT_REVIEW;
                $message = 'An amount of %1 will be captured after being approved at the payment gateway.';
            }

            if ($payment->getIsFraudDetected()) {
                $state = Order::STATE_PAYMENT_REVIEW;
                $status = Order::STATUS_FRAUD;
                $message .= ' Order is suspended as its capturing amount %1 is suspected to be fraudulent.';
            }

            if (!isset($status)) {
                $status = $this->statusResolver->getOrderStatusByState($order, $state);
            }

            $order->setState($state);
            $order->setStatus($status);

            return __($message, $order->getBaseCurrency()->formatTxt($amount));
        }

        return $proceed($payment, $amount, $order);
    }
}
