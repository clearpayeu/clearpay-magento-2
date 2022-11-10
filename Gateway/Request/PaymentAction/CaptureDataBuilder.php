<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Request\PaymentAction;

class CaptureDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    public function build(array $buildSubject): array
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $payment->getOrder();

        $isCBTCurrency = (bool) $payment->getAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_IS_CBT_CURRENCY
        );
        $cbtCurrency = $payment->getAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_CBT_CURRENCY
        );
        $currency = ($isCBTCurrency && $cbtCurrency) ? $cbtCurrency : $order->getBaseCurrencyCode();
        $token = $payment->getAdditionalInformation(\Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_TOKEN);
        $data = [
            'storeId' => $order->getStoreId(),
            'token' => $token
        ];

        if ($payment->getAdditionalInformation('clearpay_express')) {
            $data['amount'] = [
                'amount' => \Magento\Payment\Gateway\Helper\SubjectReader::readAmount($buildSubject),
                'currency' => $currency
            ];
        }

        return $data;
    }
}
