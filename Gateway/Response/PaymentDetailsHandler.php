<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Response;

class PaymentDetailsHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private \Clearpay\Clearpay\Model\Order\Payment\Auth\ExpiryDate $authExpiryDate;

    public function __construct(
        \Clearpay\Clearpay\Model\Order\Payment\Auth\ExpiryDate $authExpiryDate
    ) {
        $this->authExpiryDate = $authExpiryDate;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        if (!isset($response['id'])) {
            throw new \Magento\Payment\Gateway\Command\CommandException(
                __(
                    'Clearpay response error: Code: %1, Id: %2',
                    $response['errorCode'] ?? '',
                    $response['errorId'] ?? ''
                )
            );
        }
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        $payment->setTransactionId($response['id']);
        $payment->setIsTransactionClosed($response['openToCaptureAmount']['amount'] == 0);

        $payment->setAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_ORDER_ID,
            $response['id']
        );
        $payment->setAdditionalInformation(
            \Clearpay\Clearpay\Api\Data\CheckoutInterface::CLEARPAY_TOKEN,
            $response['token']
        );
        $payment->setAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_OPEN_TO_CAPTURE_AMOUNT,
            $response['openToCaptureAmount']['amount']
        );
        $payment->setAdditionalInformation(
            \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE,
            $response['paymentState']
        );
        if (isset($response['events'][0]['expires']) && $expires = $response['events'][0]['expires']) {
            $payment->setAdditionalInformation(
                \Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface::CLEARPAY_AUTH_EXPIRY_DATE,
                $this->authExpiryDate->format($expires)
            );
        }
    }
}
