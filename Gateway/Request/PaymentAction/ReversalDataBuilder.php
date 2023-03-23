<?php
declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Request\PaymentAction;

use Magento\Payment\Gateway\Helper\SubjectReader;

class ReversalDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    public function build(array $buildSubject): array
    {
        $payment = SubjectReader::readPayment($buildSubject);
        $clearpayToken = $payment->getPayment()->getAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_TOKEN
        );

        return [
            'storeId' => $payment->getOrder()->getStoreId(),
            'clearpayToken' => $clearpayToken
        ];
    }
}
