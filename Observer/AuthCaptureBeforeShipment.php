<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Observer;

class AuthCaptureBeforeShipment implements \Magento\Framework\Event\ObserverInterface
{
    private $shipmentCaptureProcessor;

    public function __construct(
        \Clearpay\Clearpay\Model\Order\Shipment\CaptureProcessor $shipmentCaptureProcessor
    ) {
        $this->shipmentCaptureProcessor = $shipmentCaptureProcessor;
    }

    /**
     * @throws \Magento\Framework\Exception\PaymentException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getData('shipment');

        /** @var \Magento\Sales\Model\Order\Payment $paymentInfo */
        $paymentInfo = $shipment->getOrder()->getPayment();

        if ($paymentInfo->getMethod() != \Clearpay\Clearpay\Gateway\Config\Config::CODE) {
            return;
        }

        $this->shipmentCaptureProcessor->execute($shipment);
    }
}
