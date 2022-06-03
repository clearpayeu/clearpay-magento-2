<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Setup\Patch\Data;

use Clearpay\Clearpay\Gateway\Config\Config;
use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;

class ClearpayEUAdaptDiscountFields implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private $salesSetup;
    private $json;

    public function __construct(
        \Magento\Sales\Setup\SalesSetup $salesSetup,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        $this->salesSetup = $salesSetup;
        $this->json = $json;
    }

    public static function getDependencies()
    {
        return [
            \Clearpay\Clearpay\Setup\Patch\Data\ClearpayEUAdaptPayments::class,
            \Clearpay\Clearpay\Setup\Patch\Data\ClearpayEUAdaptCapturedDiscounts::class
        ];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $payments = $this->getClearpayLegacyPaymentsInfo();
        $ordersAdditionalInfo = $this->getNewOrdersAdditionalInfo($payments);
        $this->saveOrdersAdditionalInfo($ordersAdditionalInfo);
        return $this;
    }

    private function saveOrdersAdditionalInfo(array $ordersAdditionalInfo): void
    {
        foreach ($ordersAdditionalInfo as $orderId => $additionalInfo) {
            $this->salesSetup->getConnection()->update(
                $this->salesSetup->getConnection()->getTableName('sales_order_payment'),
                ['additional_information' => $this->json->serialize($additionalInfo)],
                ['parent_id = ?' => $orderId]
            );
        }
    }

    private function getNewOrdersAdditionalInfo(array $paymentsInfo): array
    {
        $ordersAdditionalInfo = [];
        foreach ($paymentsInfo as $payment) {
            /** @var array $additionalInfo */
            $additionalInfo = $this->json->unserialize($payment['additional_information']);
            $additionalInfo[AdditionalInformationInterface::CLEARPAY_CAPTURED_DISCOUNT] = $additionalInfo[AdditionalInformationInterface::CLEARPAY_CAPTURED_DISCOUNT] ?? 0;
            $additionalInfo[AdditionalInformationInterface::CLEARPAY_ROLLOVER_DISCOUNT] = $additionalInfo[AdditionalInformationInterface::CLEARPAY_ROLLOVER_DISCOUNT] ?? 0;
            $ordersAdditionalInfo[$payment['order_id']] = $additionalInfo;
        }
        return $ordersAdditionalInfo;
    }

    private function getClearpayLegacyPaymentsInfo(): array
    {
        $connection = $this->salesSetup->getConnection();
        $select = $connection->select()
            ->from(
                ['si' => $connection->getTableName('sales_invoice')],
                ['si.order_id']
            )->joinInner(
                ['sop' => $connection->getTableName('sales_order_payment')],
                'si.order_id = sop.parent_id AND sop.method = "' . Config::CODE . '"'
                . ' AND (sop.additional_information NOT LIKE "%' . AdditionalInformationInterface::CLEARPAY_CAPTURED_DISCOUNT . '%"'
                . 'OR sop.additional_information NOT LIKE "%' . AdditionalInformationInterface::CLEARPAY_ROLLOVER_DISCOUNT . '%")',
                ['sop.additional_information']
            );
        return $connection->fetchAll($select);
    }
}
