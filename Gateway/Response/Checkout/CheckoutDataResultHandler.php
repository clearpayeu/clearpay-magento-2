<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Response\Checkout;

class CheckoutDataResultHandler extends \Clearpay\Clearpay\Gateway\Response\Checkout\CheckoutResultHandler
{
    protected function getPayment(array $handlingSubject): \Magento\Payment\Model\InfoInterface
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);
        return $paymentDO->getPayment();
    }
}
