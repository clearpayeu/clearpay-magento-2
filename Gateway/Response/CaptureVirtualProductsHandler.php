<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Response;

class CaptureVirtualProductsHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private \Magento\Payment\Gateway\CommandInterface $authCaptureCommand;
    private \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private \Clearpay\Clearpay\Model\Payment\AmountProcessor\VirtualProducts $virtualProductsAmountProcessor;
    private \Magento\Payment\Gateway\CommandInterface $voidCommand;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface                        $authCaptureCommand,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface  $paymentDataObjectFactory,
        \Clearpay\Clearpay\Model\Payment\AmountProcessor\VirtualProducts $virtualProductsAmountProcessor,
        \Magento\Payment\Gateway\CommandInterface                        $voidCommand
    ) {
        $this->authCaptureCommand = $authCaptureCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->virtualProductsAmountProcessor = $virtualProductsAmountProcessor;
        $this->voidCommand = $voidCommand;
    }

    /**
     * @throws \Magento\Payment\Gateway\Command\CommandException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        $itemsToCapture = array_filter(
            $payment->getOrder()->getAllItems(),
            static fn($item) => !$item->getParentItem() && $item->getIsVirtual()
        );

        if (count($itemsToCapture)) {
            $amountToCapture = $this->virtualProductsAmountProcessor->process($itemsToCapture, $payment);
            if ($amountToCapture > 0) {
                try {
                    $this->authCaptureCommand->execute([
                        'payment' => $this->paymentDataObjectFactory->create($payment),
                        'amount' => $amountToCapture
                    ]);

                } catch (\Throwable $e) {
                    $commandSubject = ['payment' => $paymentDO];
                    $this->voidCommand->execute($commandSubject);

                    throw $e;
                }
            }
        }
    }
}
