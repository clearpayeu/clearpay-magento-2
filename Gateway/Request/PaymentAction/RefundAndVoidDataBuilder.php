<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Request\PaymentAction;

use Magento\Payment\Gateway\Helper\SubjectReader;

class RefundAndVoidDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use \Magento\Payment\Helper\Formatter;

    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $clearpayOrderId = $paymentDO->getPayment()->getAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_ORDER_ID
        );

        $data = [
            'storeId' => $paymentDO->getOrder()->getStoreId(),
            'orderId' => $clearpayOrderId
        ];
        try {
            return array_merge($data, [
                'amount' => [
                    'amount' => $this->formatPrice(SubjectReader::readAmount($buildSubject)),
                    'currency' => $paymentDO->getOrder()->getCurrencyCode()
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return $data;
        }
    }
}
