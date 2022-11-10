<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Request\PaymentAction;

use Magento\Payment\Gateway\Helper\SubjectReader;

class AuthCaptureDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use \Magento\Payment\Helper\Formatter;

    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        $isCBTCurrency = (bool) $payment->getAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_IS_CBT_CURRENCY
        );

        $CBTCurrency = $payment->getAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_CBT_CURRENCY
        );

        $clearpayOrderId = $payment->getAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_ORDER_ID
        );

        $currencyCode = ($isCBTCurrency && $CBTCurrency)
            ? $CBTCurrency
            : $payment->getOrder()->getBaseCurrencyCode();

        return [
            'storeId' => $paymentDO->getOrder()->getStoreId(),
            'orderId' => $clearpayOrderId,
            'amount' => [
                'amount' => $this->formatPrice(SubjectReader::readAmount($buildSubject)),
                'currency' => $currencyCode
            ]
        ];
    }
}
