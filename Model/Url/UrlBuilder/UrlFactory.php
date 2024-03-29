<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Url\UrlBuilder;

class UrlFactory
{
    private \Clearpay\Clearpay\Model\Config $config;
    private \Magento\Store\Model\StoreManagerInterface $storeManager;
    private array $environments;

    public function __construct(
        \Clearpay\Clearpay\Model\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $environments = []
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->environments = $environments;
    }

    public function create(string $type, ?int $storeId = null, array $pathArgs = []): string
    {
        $apiMode = $this->config->getApiMode($pathArgs['websiteId'] ?? null);
        $item = $this->environments[$apiMode][$type] ?? false;

        if (!$item) {
            throw new \InvalidArgumentException('Clearpay environment url config is not found');
        }
        if (is_string($item)) {
            return $item;
        }

        if (isset($pathArgs['websiteId'])) {
            $currencyCode = $this->config->getMerchantCurrency(
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                $pathArgs['websiteId']
            );
        }
        if (!isset($currencyCode)) {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->storeManager->getStore($storeId);
            $currencyCode = $store->getCurrentCurrencyCode();
        }

        return $item[$currencyCode] ?? $item['default'];
    }
}
