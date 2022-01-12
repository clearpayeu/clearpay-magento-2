<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment\Capture;

use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;

class CancelOrderProcessor
{
    private $paymentDataObjectFactory;
    private $refundCommand;
    private $voidCommand;

    public function __construct(
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Magento\Payment\Gateway\CommandInterface $refundCommand,
        \Magento\Payment\Gateway\CommandInterface $voidCommand
    ) {
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->refundCommand = $refundCommand;
        $this->voidCommand = $voidCommand;
    }

    /**
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(\Magento\Sales\Model\Order\Payment $payment): void
    {
        $commandSubject = ['payment' => $this->paymentDataObjectFactory->create($payment)];

        $paymentState = $payment->getAdditionalInformation(AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE);
        if ($paymentState == \Clearpay\Clearpay\Model\PaymentStateInterface::AUTH_APPROVED) {
            $this->voidCommand->execute($commandSubject);
        } else {
            $this->refundCommand->execute(array_merge($commandSubject, [
                'amount' => $payment->getBaseAmountOrdered()
            ]));
        }
    }
}
