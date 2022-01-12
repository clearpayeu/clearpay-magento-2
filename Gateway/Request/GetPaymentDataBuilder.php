<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;

class GetPaymentDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    public function build(array $buildSubject): array
    {
        $payment = SubjectReader::readPayment($buildSubject);

        $clearpayOrderId = $payment->getPayment()->getAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_ORDER_ID
        );
        return [
            'storeId' => $payment->getOrder()->getStoreId(),
            'orderId' => $clearpayOrderId,
        ];
    }
}
