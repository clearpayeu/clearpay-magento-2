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

        $isCBTCurrency = (bool) $paymentDO->getPayment()->getAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_IS_CBT_CURRENCY
        );
        $CBTCurrency = $paymentDO->getPayment()->getAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_CBT_CURRENCY
        );
        $currencyCode = ($isCBTCurrency && $CBTCurrency) ? $CBTCurrency : $paymentDO->getOrder()->getCurrencyCode();

        $data = [
            'storeId' => $paymentDO->getOrder()->getStoreId(),
            'orderId' => $clearpayOrderId
        ];
        try {
            return array_merge($data, [
                'amount' => [
                    'amount' => $this->formatPrice(SubjectReader::readAmount($buildSubject)),
                    'currency' => $currencyCode
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return $data;
        }
    }
}
