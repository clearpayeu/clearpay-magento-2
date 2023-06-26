<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Plugin\Catalog\Model\ResourceModel\Category;

class Tree
{
    private $categorySourceRegistry;

    public function __construct(\Clearpay\Clearpay\Model\Config\CategorySourceRegistry $categorySourceRegistry)
    {
        $this->categorySourceRegistry = $categorySourceRegistry;
    }

    public function beforeAddCollectionData(
        \Magento\Catalog\Model\ResourceModel\Category\Tree $subject,
        $collection = null,
        $sorted = false,
        $exclude = [],
        $toLoad = true,
        $onlyActive = false
    ): array {
        return [
            $collection,
            $sorted,
            $exclude,
            $toLoad,
            $this->categorySourceRegistry->getShowAllCategories() ? false : $onlyActive
        ];
    }
}
