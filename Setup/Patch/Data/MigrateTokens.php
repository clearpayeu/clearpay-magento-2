<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Setup\Patch\Data;

use Clearpay\Clearpay\Api\Data\CheckoutInterface;
use Clearpay\Clearpay\Api\Data\TokenInterface;
use Clearpay\Clearpay\Gateway\Config\Config;
use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;
use Clearpay\Clearpay\Model\ResourceModel\Token;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class MigrateTokens implements DataPatchInterface
{
    protected string $paymentCode = Config::CODE;
    private Token $tokensResource;
    private SerializerInterface $serializer;
    private DateTime $dateTime;

    public function __construct(
        Token               $tokensResource,
        SerializerInterface $serializer,
        DateTime            $dateTime
    ) {
        $this->tokensResource = $tokensResource;
        $this->serializer = $serializer;
        $this->dateTime = $dateTime;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [AdaptPayments::class];
    }

    public function apply(): self
    {
        $paymentsSelect = $this->tokensResource->getConnection()
            ->select()
            ->from($this->tokensResource->getTable('sales_order_payment'))
            ->where(OrderPaymentInterface::METHOD . ' = ?', $this->paymentCode);

        $payments = $this->tokensResource->getConnection()->fetchAll($paymentsSelect);
        $tokenEntries = [];
        foreach ($payments as $payment) {
            if (!empty($payment[OrderPaymentInterface::ADDITIONAL_INFORMATION])) {
                $additionalInfo = $this->serializer->unserialize($payment[OrderPaymentInterface::ADDITIONAL_INFORMATION]);// @codingStandardsIgnoreLine
                $token = $additionalInfo[CheckoutInterface::CLEARPAY_TOKEN] ?? null;
                if ($token) {
                    $expiration = $additionalInfo[AdditionalInformationInterface::CLEARPAY_AUTH_EXPIRY_DATE] ?? null;
                    if ($expiration) {
                        $expiration = $this->dateTime->formatDate($expiration);
                    }
                    $tokenEntries[] = [
                        TokenInterface::ORDER_ID_FIELD        => $payment['parent_id'],
                        TokenInterface::TOKEN_FIELD           => $token,
                        TokenInterface::EXPIRATION_DATE_FIELD => $expiration
                    ];
                }
            }
        }

        if (!empty($tokenEntries)) {
            $this->tokensResource->getConnection()->insertOnDuplicate($this->tokensResource->getMainTable(), $tokenEntries); // @codingStandardsIgnoreLine
        }

        return $this;
    }
}
