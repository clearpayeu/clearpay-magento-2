<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\ResourceModel;

class NotAllowedProductsProvider
{
    private $config;
    private $resourceConnection;

    public function __construct(
        \Clearpay\Clearpay\Model\Config           $config,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->config = $config;
        $this->resourceConnection = $resourceConnection;
    }

    public function provideIds(?int $storeId = null): array
    {
        $excludedCategoriesIds = $this->config->getExcludeCategories($storeId);
        if (empty($excludedCategoriesIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['cat' => $this->resourceConnection->getTableName('catalog_category_product')],
            'cat.product_id'
        )->where($connection->prepareSqlCondition('cat.category_id', ['in' => $excludedCategoriesIds]));

        return $connection->fetchCol($select);
    }
}
