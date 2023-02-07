<?php

namespace Clearpay\Clearpay\Setup;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Clearpay\Clearpay\Model\Payovertime;
/**
 * Upgrade Data script
 */

class UpgradeData implements UpgradeDataInterface
{
    protected const METHOD_CODE = 'clearpayeupayovertime';
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private $salesSetup;
    private $json;

    public function __construct(
        ModuleDataSetupInterface $salesSetup,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        $this->salesSetup = $salesSetup;
        $this->json = $json;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {

        $this->salesSetup = $setup;

        if ($context->getVersion()
            && version_compare($context->getVersion(), '3.4.4') < 0
        ) {

            $this->adaptPayment();
            $this->ClearpayEUAdaptDiscount();

        }

    }
    private function adaptPayment(){
        $this->salesSetup->startSetup();
        $table = $this->salesSetup->getTable('sales_order_payment');

        $this->salesSetup->getConnection()
            ->update($table,[
                'method' => 'clearpay',
                'additional_information' => new \Zend_Db_Expr(
                    'replace(
                            replace(
                                additional_information,
                                "clearpay_payment_status",
                                "clearpay_payment_state"
                                ),
                            "APPROVED",
                            "CAPTURED"
                        )'
                )
            ], ['method = ?' => static::METHOD_CODE]);
        $this->salesSetup->endSetup();
    }
    private function ClearpayEUAdaptDiscount(){
        $payments = $this->getClearpayLegacyPaymentsInfo();
        $ordersAdditionalInfo = $this->getNewOrdersAdditionalInfo($payments);
        $this->saveOrdersAdditionalInfo($ordersAdditionalInfo);

    }
    private function getClearpayLegacyPaymentsInfo(): array
    {
        $this->salesSetup->startSetup();
        $tableName = $this->salesSetup->getTable('sales_invoice');
        $paymentTableName = $this->salesSetup->getTable('sales_order_payment');
        $connection = $this->salesSetup->getConnection();
        $select = $connection->select()
            ->from(
                ['si' => $tableName],
                ['si.order_id']
            )->joinInner(
                ['sop' => $paymentTableName],
                'si.order_id = sop.parent_id AND sop.method = "clearpay"'
                . ' AND (sop.additional_information NOT LIKE "%' . Payovertime::ROLLOVER_DISCOUNT . '%")',
                ['sop.additional_information']
            );
        $data=$connection->fetchAll($select);
        $this->salesSetup->endSetup();
        return $data;
    }
    private function saveOrdersAdditionalInfo( $ordersAdditionalInfo)
    {
        $this->salesSetup->startSetup();
        $table = $this->salesSetup->getTable('sales_order_payment');
        foreach ($ordersAdditionalInfo as $orderId => $additionalInfo) {
            $this->salesSetup->getConnection()->update(
                $this->salesSetup->getConnection()->getTableName('sales_order_payment'),
                ['additional_information' => $this->json->serialize($additionalInfo)],
                ['parent_id = ?' => $orderId]
            );
        }
        $this->salesSetup->endSetup();
    }

    private function getNewOrdersAdditionalInfo(array $paymentsInfo): array
    {
        $this->salesSetup->startSetup();
        $ordersAdditionalInfo = [];
        foreach ($paymentsInfo as $payment) {
            /** @var array $additionalInfo */
            $additionalInfo = $this->json->unserialize($payment['additional_information']);
            $additionalInfo[Payovertime::ROLLOVER_DISCOUNT] = $additionalInfo[Payovertime::ROLLOVER_DISCOUNT] ?? 0;
            $ordersAdditionalInfo[$payment['order_id']] = $additionalInfo;
        }
        $this->salesSetup->endSetup();
        return $ordersAdditionalInfo;
    }


}