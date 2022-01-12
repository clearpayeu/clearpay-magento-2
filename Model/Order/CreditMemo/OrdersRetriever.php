<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Order\CreditMemo;

use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;

class OrdersRetriever
{
    private $orderCollectionFactory;
    private $resourceConnection;
    private $serializer;
    private $logger;

    public function __construct(
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Sales\Model\Order[]
     */
    public function getClearpayOrders(): array
    {
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection
            ->addFieldToFilter(
                'state',
                ['eq' => \Magento\Sales\Model\Order::STATE_PROCESSING]
            );
        $orderCollection = $this->joinClearpayPaymentAdditionalInfo($orderCollection);
        /** @var \Magento\Sales\Model\Order[] $items */
        $items = $orderCollection->getItems();
        $items = $this->getItemsWithAdditionalInfo($items);
        return $items;
    }

    /**
     * @var \Magento\Sales\Model\Order[] $items
     * @return \Magento\Sales\Model\Order[]
     */
    private function getItemsWithAdditionalInfo(array $items): array
    {
        $itemsWithJsonAdditionalInfo = [];
        foreach ($items as $item) {
            $additionalInformation = $item->getData(
                \Magento\Sales\Api\Data\OrderPaymentInterface::ADDITIONAL_INFORMATION
            );
            try {
                $unserializedInfo = $this->serializer->unserialize($additionalInformation);
                if (!is_array($unserializedInfo)) {
                    continue;
                }
                /** @var array $unserializedInfo */
                $item->setData(
                    \Magento\Sales\Api\Data\OrderPaymentInterface::ADDITIONAL_INFORMATION,
                    $unserializedInfo
                );
                $isAdditionalInfoFull =
                    isset($unserializedInfo[AdditionalInformationInterface::CLEARPAY_PAYMENT_STATE]) &&
                    isset($unserializedInfo[AdditionalInformationInterface::CLEARPAY_OPEN_TO_CAPTURE_AMOUNT]) &&
                    isset($unserializedInfo[AdditionalInformationInterface::CLEARPAY_AUTH_EXPIRY_DATE]);
                if ($isAdditionalInfoFull) {
                    $itemsWithJsonAdditionalInfo[] = $item;
                }
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning($e->getMessage());
            }
        }
        return $itemsWithJsonAdditionalInfo;
    }

    private function joinClearpayPaymentAdditionalInfo(
        \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection
    ): \Magento\Sales\Model\ResourceModel\Order\Collection {
        $salesOrderPaymentTable = $this->resourceConnection->getConnection()->getTableName('sales_order_payment');
        $orderCollection->join(
            ['sop' => $salesOrderPaymentTable],
            'sop.parent_id = main_table.entity_id',
            \Magento\Sales\Api\Data\OrderPaymentInterface::ADDITIONAL_INFORMATION
        );
        $selectSql = $orderCollection->getSelectSql();
        /** @var \Magento\Framework\DB\Select $selectSql */
        $selectSql
            ->where(
                'sop.method = ?',
                \Clearpay\Clearpay\Gateway\Config\Config::CODE
            )
            ->where(
                'sop.additional_information like ?',
                '%' . AdditionalInformationInterface::CLEARPAY_AUTH_EXPIRY_DATE . '%'
            );
        return $orderCollection;
    }
}
