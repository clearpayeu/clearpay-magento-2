<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Config\Source;

class Category implements \Magento\Framework\Data\OptionSourceInterface
{
    private $storeManager;
    private $request;
    private $categoryHelper;
    private $categorySourceRegistry;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Clearpay\Clearpay\Model\Config\CategorySourceRegistry $categorySourceRegistry
    ) {
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->categoryHelper = $categoryHelper;
        $this->categorySourceRegistry = $categorySourceRegistry;
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->getCategoriesTree() as $categoryData) {
            $this->renderSubCategory($categoryData, $options);
        }
        return array_reverse($options);
    }

    private function renderSubCategory(array $categoryData, array &$optionsResult): void
    {
        if (isset($categoryData['children'])) {
            foreach ($categoryData['children'] as $subCatData) {
                $this->renderSubCategory($subCatData, $optionsResult);
            }
        }

        if (!isset($categoryData['level']) || !is_numeric($categoryData['level']) || $categoryData['level'] < 2) {
            return;
        }

        $optionsResult[] = [
            'label' => str_repeat('―', $categoryData['level'] - 2) . $categoryData['label'],
            'value' => $categoryData['id']
        ];
    }

    private function getCategoriesTree(): array
    {
        $currentStoreId = $this->storeManager->getStore()->getId();

        $this->storeManager->setCurrentStore($this->getStoreIdByRequest() ?? $currentStoreId);
        $this->categorySourceRegistry->setShowAllCategories(true);
        /** @var \Magento\Framework\Data\Collection $categories */
        $categories = $this->categoryHelper->getStoreCategories(false, true);
        $this->categorySourceRegistry->setShowAllCategories(false);
        $this->storeManager->setCurrentStore($currentStoreId);

        return $this->convertToTree($categories);
    }

    private function convertToTree(\Magento\Framework\Data\Collection $categories): array
    {
        $categoryById = [];
        foreach ($categories as $category) {
            foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                if (!isset($categoryById[$categoryId])) {
                    $categoryById[$categoryId] = ['id' => $categoryId, 'children' => []];
                }
            }
            $categoryById[$category->getId()]['label'] = $category->getName();
            $categoryById[$category->getId()]['level'] = $category->getLevel();
            $categoryById[$category->getParentId()]['children'][] = & $categoryById[$category->getId()];
        }
        foreach ($categoryById as $categoryData) {
            if (!isset($categoryData['label']) && isset($categoryData['children'])) {
                return $categoryData['children'];
            }
        }
        return [];
    }

    private function getStoreIdByRequest(): ?int
    {
        if ($storeId = $this->request->getParam('store')) {
            return (int)$storeId;
        }
        if ($websiteId = $this->request->getParam('website')) {
            /** @var \Magento\Store\Model\Website $website */
            $website = $this->storeManager->getWebsite($websiteId);
            return (int)$website->getDefaultStore()->getId();
        }
        return null;
    }
}
